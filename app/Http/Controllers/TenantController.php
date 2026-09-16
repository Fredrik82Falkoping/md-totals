<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TenantController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_admin) {
            session(['tenant_id' => auth()->user()->tenant_id]);

            return redirect()->route('statistics.index');
        }

        $tenants = Tenant::orderBy('name')->get();

        return view('tenants.select', compact('tenants'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $request->validate(['tenant_id' => 'required|exists:tenants,id']);

        $request->session()->put('tenant_id', $request->tenant_id);

        return redirect()->route('statistics.index');
    }

    public function edit(Tenant $tenant)
    {
        abort_unless(auth()->user()->is_admin, 403);

        return view('tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $tenant->update(['name' => $validated['name']]);

        if (!empty($validated['password'])) {
            $tenant->users()->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()
            ->route('tenants.select')
            ->with('status', 'Butiken har uppdaterats.');
    }
}