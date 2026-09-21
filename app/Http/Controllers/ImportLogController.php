<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Tenant;
use App\Services\MarkdownImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImportLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $validated = $request->validate([
            'tenant' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Log::query()->with('tenant')->latest('started_at');

        $query->when($validated['tenant'] ?? null, function ($query, string $tenantName) {
            $query->whereHas('tenant', function ($tenantQuery) use ($tenantName) {
                $tenantQuery->where('name', 'like', "%{$tenantName}%");
            });
        });

        $query->when($validated['from'] ?? null, fn ($query, string $from) =>
            $query->where('started_at', '>=', Carbon::parse($from)->startOfMinute())
        );

        $query->when($validated['to'] ?? null, fn ($query, string $to) =>
            $query->where('started_at', '<=', Carbon::parse($to)->endOfMinute())
        );

        return view('import-logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'tenants' => Tenant::whereNotNull('api_endpoint')->orderBy('name')->get(),
            'filters' => [
                'tenant' => $validated['tenant'] ?? '',
                'from' => $validated['from'] ?? '',
                'to' => $validated['to'] ?? '',
            ],
        ]);
    }

    public function importNewTenant(Request $request, MarkdownImportService $importService)
    {
        abort_unless(auth()->user()->is_admin, 403);
        set_time_limit(300);

        $validated = $request->validate([
            'tenant_endpoint' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}._-]+$/u', Rule::unique('tenants', 'api_endpoint')],
            'store_code' => ['required', 'string', 'max:255', Rule::unique('tenants', 'store_code')],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $importService->importNewTenant(
                $validated['tenant_endpoint'],
                $validated['store_code'],
                $validated['name'] ?? null,
            );

            return redirect()->route('import-logs.index')
                ->with('status', "Kunden {$result['tenant']->name} importerades. {$result['count']} nya rader.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function importTenant(Request $request, MarkdownImportService $importService)
    {
        abort_unless(auth()->user()->is_admin, 403);
        set_time_limit(300);

        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);
        $tenant = Tenant::findOrFail($validated['tenant_id']);

        try {
            $count = $importService->importForTenant($tenant, $tenant->api_endpoint);

            return redirect()->route('import-logs.index')
                ->with('status', "Importen för {$tenant->name} är klar. {$count} nya rader.");
        } catch (\Throwable $e) {
            return redirect()->route('import-logs.index')
                ->with('error', "Importen för {$tenant->name} misslyckades: {$e->getMessage()}");
        }
    }

    public function importAll(MarkdownImportService $importService)
    {
        abort_unless(auth()->user()->is_admin, 403);
        set_time_limit(300);

        $tenants = Tenant::whereNotNull('api_endpoint')->orderBy('name')->get();
        $successful = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                $importService->importForTenant($tenant, $tenant->api_endpoint);
                $successful++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $message = "Importen för alla {$tenants->count()} tenants är klar. {$successful} lyckades";
        if ($failed > 0) {
            $message .= ", {$failed} misslyckades";
        }

        return redirect()->route('import-logs.index')
            ->with($failed > 0 ? 'error' : 'status', $message . '.');
    }
}