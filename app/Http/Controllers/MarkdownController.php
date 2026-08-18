<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MarkdownController extends Controller
{
    private array $sortable = [
        'product_id', 'product_name', 'category', 'scanned_at', 'regular_price',
        'reduced_price', 'discount_amount', 'discount_percent',
    ];

    public function index(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $tenant = Tenant::find($tenantId);
        $query = Markdown::query();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('week')) {
            [$start, $end] = $this->weekToDateRange($request->input('week'));
            $query->whereBetween('scanned_at', [$start, $end]);
        }

        if ($request->filled('month')) {
            [$start, $end] = $this->monthToDateRange($request->input('month'));
            $query->whereBetween('scanned_at', [$start, $end]);
        }

        if ($request->filled('year')) {
            [$start, $end] = $this->yearToDateRange($request->input('year'));
            $query->whereBetween('scanned_at', [$start, $end]);
        }

        $sortColumn = $request->input('sort', 'scanned_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if (!in_array($sortColumn, $this->sortable, true)) {
            $sortColumn = 'scanned_at';
        }

        $query->orderBy($sortColumn, $sortDirection);

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
            'currentSort' => $sortColumn,
            'currentDirection' => $sortDirection,
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