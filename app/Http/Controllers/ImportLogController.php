<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'filters' => [
                'tenant' => $validated['tenant'] ?? '',
                'from' => $validated['from'] ?? '',
                'to' => $validated['to'] ?? '',
            ],
        ]);
    }
}