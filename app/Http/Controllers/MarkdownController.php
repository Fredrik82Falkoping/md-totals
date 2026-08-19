<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class MarkdownController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);
        $query = Markdown::query();

        $this->applyFilters($query, $request);

        $discountPercents = Markdown::whereNotNull('discount_percent')
            ->distinct()
            ->orderBy('discount_percent')
            ->pluck('discount_percent');

        if (!empty($allDateRanges)) {
            // Vi grupperar alla datumfrågor inuti en subquery för att inte krocka med kategorifiltret
            $query->where(function ($subQuery) use ($allDateRanges) {
                foreach ($allDateRanges as $index => $range) {
                    [$start, $end] = $range;

                    if ($index === 0) {
                        $subQuery->whereBetween('scanned_at', [$start, $end]);
                    } else {
                        $subQuery->orWhereBetween('scanned_at', [$start, $end]);
                    }
                }
            });
        }

        $baseQuery = clone $query; // Clone the base query for summary calculations

        $query->selectRaw('
            product_id,
            MAX(name) as product_name, -- Hämtar namnet (MAX fungerar bra eftersom namnet är likadant per ID)
            COUNT(*) as total_scans,    -- Hur många gånger produkten scannats totalt
            SUM(quantity) as total_quantity,
            SUM(purchase_price) as total_purchase_price,
            SUM(reduced_price) as total_reduced_price,
            SUM(margin_amount) as total_margin_amount,
            AVG(discount_percent) as avg_discount_percent,
            AVG(margin_percent) as avg_margin_percent
        ')
        ->groupBy('product_id');

        $currentSort = $request->input('sort', 'product_name');
        $currentDirection = $request->input('direction', 'asc');

        $sortMapping = [
            'product_name' => 'product_name',
            'quantity' => 'total_quantity',
            'purchase_price' => 'total_purchase_price',
            'reduced_price' => 'total_reduced_price',
            'margin_amount' => 'total_margin_amount',
            'discount_percent' => 'avg_discount_percent',
            'margin_percent' => 'avg_margin_percent',
        ];

        if (!array_key_exists($currentSort, $sortMapping)) {
            $currentSort = 'product_name';
        }

        $currentDirection = $currentDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortMapping[$currentSort], $currentDirection);

        $summary = [
            'total_count' => (clone $baseQuery)->sum('quantity'),
            'total_discount' => (clone $baseQuery)->sum('discount_amount'),
            'average_discount_percent' => (clone $baseQuery)->avg('discount_percent'),
            'total_margin' => (clone $baseQuery)->sum('margin_amount'),
        ];

        // $markdowns = $query->limit(100)->get();

        $discountPercents = Markdown::whereNotNull('discount_percent')
            ->distinct()
            ->orderBy('discount_percent')
            ->pluck('discount_percent');

        $categories = Markdown::whereNotNull('category')->distinct()->pluck('category');
        $weeks = $this->availablePeriods('week');
        $months = $this->availablePeriods('month');
        $years = $this->availablePeriods('year');

        return view('statistics.index', [
            'tenant' => $tenant,
            'tenant_name' => $tenant->name,
            'summary' => $summary,
            'markdowns' => $query->limit(100)->get(),
            'categories' => $categories,
            'weeks' => $weeks,
            'months' => $months,
            'years' => $years,
            'currentCategory' => $request->input('category'),
            'currentWeek' => $request->input('week'),
            'currentMonth' => $request->input('month'),
            'currentYear' => $request->input('year'),
            'discountPercents' => $discountPercents,
            'currentDiscountPercents' => $request->input('discount_percent', []),
            'currentSort' => $currentSort,
            'currentDirection' => $currentDirection,
        ]);
    }

    private function weekToDateRange(string $isoWeek): array
    {
        // $isoWeek format: "2023-W08"
        [$year, $week] = sscanf($isoWeek, '%d-W%d');

        $start = Carbon::create()->setISODate($year, $week)->startOfWeek();
        $end = (clone $start)->endOfWeek();

        return [$start, $end];
    }

    private function monthToDateRange(string $month): array
    {
        // $month format: "2023-08"
        [$year, $month] = sscanf($month, '%d-%d');

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [$start, $end];
    }

    private function yearToDateRange(string $year): array
    {
        $start = Carbon::create((int) $year, 1, 1)->startOfYear();
        $end = (clone $start)->endOfYear();

        return [$start, $end];
    }

     /**
     * Tar emot en array med blandade tidsfilter (t.ex. ["2023-W08", "2023-08", "2024"])
     * och returnerar en array med start- och slutdatum för varje val.
     *
     * @param array|string|null $filters
     * @return array
     */
    public function getMultipleFilterRanges(array|string|null $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        // Säkerställ att vi alltid jobbar med en array, även om det bara skickades en singelsträng
        $filterArray = is_array($filters) ? $filters : [$filters];
        $ranges = [];

        foreach ($filterArray as $value) {
            if (empty($value)) {
                continue;
            }

            // Matcha vecka: t.ex. "2023-W08"
            if (preg_match('/^\d{4}-W\d{1,2}$/', $value)) {
                $ranges[] = $this->weekToDateRange($value);
            }
            // Matcha månad: t.ex. "2023-08"
            elseif (preg_match('/^\d{4}-\d{2}$/', $value)) {
                $ranges[] = $this->monthToDateRange($value);
            }
            // Matcha år: t.ex. "2024"
            elseif (preg_match('/^\d{4}$/', $value)) {
                $ranges[] = $this->yearToDateRange($value);
            }
        }

        return $ranges;
    }

    private function availableWeeks(): array
    {
        return Markdown::query()
            ->pluck('scanned_at')
            ->map(fn ($date) => Carbon::parse($date)->isoFormat('GGGG-[W]WW'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function availablePeriods(string $period): array
    {
        return Markdown::query()
            ->pluck('scanned_at')
            ->map(function ($date) use ($period) {
                $date = Carbon::parse($date);

                return match ($period) {
                    'week' => $date->isoFormat('GGGG-[W]WW'),
                    'month' => $date->format('Y-m'),
                    'year' => $date->format('Y'),
                    default => throw new InvalidArgumentException(
                        "Unknown period: {$period}"
                    ),
                };
            })
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function productDetail(Request $request, string $productId)
    {
        // Tenant-filtrering sker automatiskt via global scope på Markdown
        $query = Markdown::where('product_id', $productId);
        $this->applyFilters($query, $request);

        $events = $query
            ->orderByDesc('scanned_at')
            ->get([
                'product_id',
                'name',
                'scanned_at',
                'regular_price',
                'reduced_price',
                'discount_amount',
                'discount_percent',
            ]);

        if ($events->isEmpty()) {
            return response()->json(['message' => 'No data found for this product.'], 404);
        }

        return response()->json([
            'product_id' => $productId,
            'name' => $events->first()->name,
            'events' => $events->map(function ($event) {
                return [
                    'scanned_at' => $event->scanned_at->format('Y-m-d H:i'),
                    'regular_price' => number_format($event->regular_price, 2),
                    'reduced_price' => number_format($event->reduced_price, 2),
                    'discount_amount' => number_format($event->discount_amount, 2),
                    'discount_percent' => number_format($event->discount_percent, 1),
                ];
            }),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('category')) {
            $query->whereIn('category', (array) $request->input('category'));
        }

        $allDateRanges = [];

        foreach (['week', 'month', 'year'] as $period) {
            if ($request->filled($period)) {
                $allDateRanges = array_merge(
                    $allDateRanges,
                    $this->getMultipleFilterRanges($request->input($period))
                );
            }
        }

        if ($request->filled('discount_percent')) {
            $query->whereIn('discount_percent', $request->input('discount_percent'));
        }

        if (!empty($allDateRanges)) {
            $query->where(function ($subQuery) use ($allDateRanges) {
                foreach ($allDateRanges as $index => $range) {
                    [$start, $end] = $range;

                    if ($index === 0) {
                        $subQuery->whereBetween('scanned_at', [$start, $end]);
                    } else {
                        $subQuery->orWhereBetween('scanned_at', [$start, $end]);
                    }
                }
            });
        }
    }

    public function compare(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);

        $periodType = $request->input('period_type', 'week');
        if (!in_array($periodType, ['week', 'month', 'year'], true)) {
            $periodType = 'week';
        }

        $periodAValue = $request->input('period_a');
        $periodBValue = $request->input('period_b');
        $categories = $request->input('category', []); // array, multiselect

        $rangeA = $periodAValue ? $this->periodToDateRange($periodType, $periodAValue) : null;
        $rangeB = $periodBValue ? $this->periodToDateRange($periodType, $periodBValue) : null;

        $markdownsA = $rangeA ? $this->markdownsForRange($rangeA, $categories) : collect();
        $markdownsB = $rangeB ? $this->markdownsForRange($rangeB, $categories) : collect();

        $summaryA = $rangeA ? $this->summaryFromCollection($markdownsA) : null;
        $summaryB = $rangeB ? $this->summaryFromCollection($markdownsB) : null;

        return view('statistics.compare', [
            'tenant' => $tenant,
            'tenant_name' => $tenant->name,
            'periodType' => $periodType,
            'periodAValue' => $periodAValue,
            'periodBValue' => $periodBValue,
            'weeks' => $this->availablePeriods('week'),
            'months' => $this->availablePeriods('month'),
            'years' => $this->availablePeriods('year'),
            'allCategories' => Markdown::whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'currentCategories' => $categories,
            'summaryA' => $summaryA,
            'summaryB' => $summaryB,
            'markdownsA' => $markdownsA,
            'markdownsB' => $markdownsB,
        ]);
    }

    private function markdownsForRange(array $range, array $categories = [])
    {
        $query = Markdown::whereBetween('scanned_at', [$range[0], $range[1]]);

        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }

        return $query->orderByDesc('scanned_at')->limit(200)->get();
    }

    private function summaryFromCollection($markdowns): array
    {
        return [
            'total_count' => $markdowns->count(),
            'total_discount' => $markdowns->sum('discount_amount'),
            'average_discount_percent' => $markdowns->avg('discount_percent'),
            'total_regular_value' => $markdowns->sum('regular_price'),
            'total_reduced_value' => $markdowns->sum('reduced_price'),
        ];
    }

    /* public function compare(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);

        $periodType = $request->input('period_type', 'week'); // week | month | year
        if (!in_array($periodType, ['week', 'month', 'year'], true)) {
            $periodType = 'week';
        }

        $periodAValue = $request->input('period_a');
        $periodBValue = $request->input('period_b');

        $rangeA = $periodAValue ? $this->periodToDateRange($periodType, $periodAValue) : null;
        $rangeB = $periodBValue ? $this->periodToDateRange($periodType, $periodBValue) : null;

        $summaryA = $rangeA ? $this->summaryForRange($rangeA) : null;
        $summaryB = $rangeB ? $this->summaryForRange($rangeB) : null;

        return view('statistics.compare', [
            'tenant' => $tenant,
            'tenant_name' => $tenant->name,
            'periodType' => $periodType,
            'periodAValue' => $periodAValue,
            'periodBValue' => $periodBValue,
            'weeks' => $this->availablePeriods('week'),
            'months' => $this->availablePeriods('month'),
            'years' => $this->availablePeriods('year'),
            'summaryA' => $summaryA,
            'summaryB' => $summaryB,
        ]);
    }
 */

    private function summaryForRange(array $range): array
    {
        $query = Markdown::whereBetween('scanned_at', [$range[0], $range[1]]);

        return [
            'total_count' => (clone $query)->count(),
            'total_discount' => (clone $query)->sum('discount_amount'),
            'average_discount_percent' => (clone $query)->avg('discount_percent'),
            'total_regular_value' => (clone $query)->sum('regular_price'),
            'total_reduced_value' => (clone $query)->sum('reduced_price'),
        ];
    }

    private function periodToDateRange(string $type, string $value): array
    {
        return match ($type) {
            'week' => $this->weekToDateRange($value),
            'month' => $this->monthToDateRange($value),
            'year' => $this->yearToDateRange($value),
        };
    }

}