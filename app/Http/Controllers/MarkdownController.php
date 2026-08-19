<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;
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

        // Handle categories (Multiselect med whereIn)
        if ($request->filled('category')) {
            // Säkerställ att det hanteras som en array oavsett om det är ett eller flera val
            $query->whereIn('category', (array) $request->input('category'));
        }

        // Filter date intervals
        $allDateRanges = [];

        if ($request->filled('week')) {
            $allDateRanges = array_merge($allDateRanges, $this->getMultipleFilterRanges($request->input('week')));
        }

        if ($request->filled('month')) {
            $allDateRanges = array_merge($allDateRanges, $this->getMultipleFilterRanges($request->input('month')));
        }

        if ($request->filled('year')) {
            $allDateRanges = array_merge($allDateRanges, $this->getMultipleFilterRanges($request->input('year')));
        }

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
            'total_count' => (clone $query)->count(),
            'total_discount' => (clone $query)->sum('discount_amount'),
            'average_discount_percent' => (clone $query)->avg('discount_percent'),
        ];

        // $markdowns = $query->limit(100)->get();

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
}