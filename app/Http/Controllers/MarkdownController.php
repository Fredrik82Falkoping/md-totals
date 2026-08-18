<?php

namespace App\Http\Controllers;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Http\Request;

class MarkdownController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->session()->get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenants.select');
        }

        $query = Markdown::where('tenant_id', $tenantId);

        $summary = [
            'total_count' => Markdown::count(),
            'total_discount' => Markdown::sum('discount_amount'),
            'average_discount_percent' => Markdown::avg('discount_percent'),
        ];

        $latest = Markdown::orderByDesc('scanned_at')->limit(50)->get();

        $tenantName = Tenant::find($tenantId)->name ?? 'Unknown Tenant';

        return view('statistics.index', [
            'tenant_name' => $tenantName,
            'summary' => $summary,
            'markdowns' => $latest,
        ]);
    }
}