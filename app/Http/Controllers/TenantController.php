<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

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
}