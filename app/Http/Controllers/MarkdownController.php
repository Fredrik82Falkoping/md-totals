<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MarkdownController extends Controller
{
    private array $sortable = [
        'product_id', 'category', 'scanned_at', 'regular_price',
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
        $weeks = $this->availableWeeks();

        return view('statistics.index', [
            'tenant' => $tenant,
            'tenant_name' => $tenant->name,
            'summary' => $summary,
            'markdowns' => $query->limit(100)->get(),
            'categories' => $categories,
            'weeks' => $weeks,
            'currentCategory' => $request->input('category'),
            'currentWeek' => $request->input('week'),
            'currentSort' => $sortColumn,
            'currentDirection' => $sortDirection,
        ]);
    }

    private function weekToDateRange(string $isoWeek): array
    {
        // $isoWeek format: "2023-W08"
        [$year, $week] = sscanf($isoWeek, '%d-W%d');

        $start = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $end = (clone $start)->endOfWeek();

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
}