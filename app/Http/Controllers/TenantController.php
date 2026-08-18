<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('name')->get();

        return view('tenants.select', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate(['tenant_id' => 'required|exists:tenants,id']);

        $request->session()->put('tenant_id', $request->tenant_id);

        return redirect()->route('statistics.index');
    }
}