<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use InvalidArgumentException;

class MarkdownController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $request->session()->forget('tenant_id');
            return redirect()->route('tenants.select');
        }

        $query = Markdown::query();

        $this->applyFilters($query, $request);

        $baseQuery = clone $query; // Används för summeringen, innan vi groupBy:ar för produktlistan

        // Produktgrupperad lista (en rad per produkt, med summerade/genomsnittliga värden)
        $query->selectRaw('
            product_id,
            MAX(name) as product_name, -- Namnet är detsamma per ID, MAX fungerar bra för att plocka ut det
            COUNT(*) as total_scans,
            SUM(quantity) as total_quantity,
            SUM(purchase_price) as total_purchase_price,
            SUM(reduced_price) as total_reduced_price,
            SUM(margin_amount) as total_margin_amount,
            AVG(discount_percent) as avg_discount_percent,
            AVG(margin_percent) as avg_margin_percent
        ')->groupBy('product_id');

        $currentSort = $request->input('sort', 'product_name');
        $currentDirection = $request->input('direction', 'asc');

        $sortMapping = [
            'product_name' => 'product_name',
            'quantity' => 'total_scans',
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

        // Summering över HELA urvalet (inte bara de rader som visas), räknat i databasen
        $summary = [
            'total_count' => (clone $baseQuery)->count(),
            'total_discount' => (clone $baseQuery)->sum('discount_amount'),
            'average_discount_percent' => (clone $baseQuery)->avg('discount_percent'),
            'total_margin' => (clone $baseQuery)->sum('margin_amount'),
        ];

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

    public function compare(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $request->session()->forget('tenant_id');
            return redirect()->route('tenants.select');
        }

        $periodType = $request->input('period_type', 'week');
        if (!in_array($periodType, ['week', 'month', 'year'], true)) {
            $periodType = 'week';
        }

        $periodAValue = $request->input('period_a');
        $periodBValue = $request->input('period_b');
        $categories = $request->input('category', []);

        $rangeA = $periodAValue ? $this->periodToDateRange($periodType, $periodAValue) : null;
        $rangeB = $periodBValue ? $this->periodToDateRange($periodType, $periodBValue) : null;

        // Summering räknas över HELA perioden i databasen - inte begränsad av detaljlistans limit(200)
        $summaryA = $rangeA ? $this->summaryForRange($rangeA, $categories) : null;
        $summaryB = $rangeB ? $this->summaryForRange($rangeB, $categories) : null;

        // Detaljlistan är separat och medvetet begränsad, bara för visning i tabellen
        $markdownsA = $rangeA ? $this->markdownsForRange($rangeA, $categories) : collect();
        $markdownsB = $rangeB ? $this->markdownsForRange($rangeB, $categories) : collect();

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

    /**
     * Applicerar gemensamma filter (kategori, vecka/månad/år, rabatt-%) på en query.
     * Används av både index() och productDetail() så filtreringen är konsekvent.
     */
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
            // Grupperar alla datumintervall i en subquery så de inte krockar med övriga filter (AND)
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

    /**
     * Summering (count/sum/avg) för ett enskilt datumintervall, räknat i databasen.
     * Används av compare() - ALDRIG begränsad av någon limit, så siffrorna blir korrekta
     * oavsett hur många rader perioden faktiskt innehåller.
     */
    private function summaryForRange(array $range, array $categories = []): array
    {
        $query = Markdown::whereBetween('scanned_at', [$range[0], $range[1]]);

        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }

        return [
            'total_count' => (clone $query)->count(),
            'total_discount' => (clone $query)->sum('discount_amount'),
            'average_discount_percent' => (clone $query)->avg('discount_percent'),
            'total_regular_value' => (clone $query)->sum('regular_price'),
            'total_reduced_value' => (clone $query)->sum('reduced_price'),
        ];
    }

    /**
     * Detaljlista (enskilda rader) för ett datumintervall - begränsad till 200 rader,
     * bara avsedd för visning i tabellen, ANVÄNDS INTE för summering.
     */
    private function markdownsForRange(array $range, array $categories = [])
    {
        $query = Markdown::whereBetween('scanned_at', [$range[0], $range[1]]);

        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }

        return $query->orderByDesc('scanned_at')->limit(200)->get();
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

    private function periodToDateRange(string $type, string $value): array
    {
        return match ($type) {
            'week' => $this->weekToDateRange($value),
            'month' => $this->monthToDateRange($value),
            'year' => $this->yearToDateRange($value),
        };
    }

    /**
     * Tar emot en array med blandade tidsfilter (t.ex. ["2023-W08", "2023-08", "2024"])
     * och returnerar en array med start- och slutdatum för varje val.
     */
    private function getMultipleFilterRanges(array|string|null $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        $filterArray = is_array($filters) ? $filters : [$filters];
        $ranges = [];

        foreach ($filterArray as $value) {
            if (empty($value)) {
                continue;
            }

            if (preg_match('/^\d{4}-W\d{1,2}$/', $value)) {
                $ranges[] = $this->weekToDateRange($value);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $value)) {
                $ranges[] = $this->monthToDateRange($value);
            } elseif (preg_match('/^\d{4}$/', $value)) {
                $ranges[] = $this->yearToDateRange($value);
            }
        }

        return $ranges;
    }

    /**
     * Returnerar tillgängliga perioder (vecka/månad/år) som finns i datan för aktuell tenant.
     * Cachas i 6h eftersom detta annars loopar Carbon över ALLA rader i PHP - dyrt vid
     * stora datamängder (orsakade tidigare ett 500-fel för butiker med 50 000+ rader).
     * Cachen rensas vid import, se MarkdownImportService::importForTenant().
     */
    private function availablePeriods(string $period): array
    {
        $tenantId = session('tenant_id');
        $cacheKey = "available_periods_{$period}_tenant_{$tenantId}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($period) {
            if ($period === 'month') {
                return Markdown::query()
                    ->selectRaw("DATE_FORMAT(scanned_at, '%Y-%m') as period_value")
                    ->distinct()
                    ->orderBy('period_value')
                    ->pluck('period_value')
                    ->toArray();
            }

            if ($period === 'year') {
                return Markdown::query()
                    ->selectRaw("DATE_FORMAT(scanned_at, '%Y') as period_value")
                    ->distinct()
                    ->orderBy('period_value')
                    ->pluck('period_value')
                    ->toArray();
            }

            if ($period === 'week') {
                // ISO-vecka kan inte beräknas korrekt i SQLite, måste göras i PHP.
                // Undviker isoFormat() (extremt långsam) - använder istället DateTime::format('o-\WW'),
                // som ger samma ISO-veckonummer men utan Carbons tunga lokaliseringssystem.
                return Markdown::query()
                    ->pluck('scanned_at')
                    ->map(function ($date) {
                        $dt = new \DateTime($date);
                        return $dt->format('o-\WW'); // t.ex. "2026-W12"
                    })
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            }

            throw new InvalidArgumentException("Unknown period: {$period}");
        });
    }
}