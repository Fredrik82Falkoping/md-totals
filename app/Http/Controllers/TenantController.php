<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
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

        $user = $tenant->users()->first();

        return view('tenants.edit', compact('tenant', 'user'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $user = $tenant->users()->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [$user ? 'nullable' : 'required', 'string', 'max:255', 'unique:users,username'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
        ]);

        $tenant->update(['name' => $validated['name']]);

        if ($user) {
            if (!empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }
        } else {
            User::create([
                'name' => $validated['username'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id,
                'is_admin' => false,
            ]);
        }

        return redirect()
            ->route('tenants.select')
            ->with('status', 'Butiken har uppdaterats.');
    }
}