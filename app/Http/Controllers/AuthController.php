<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $user = \App\Models\User::where('name', $credentials['username'])
            ->orWhere('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Felaktigt användarnamn eller lösenord.'])
                ->onlyInput('username');
        }

        if (!$user->is_admin && !$user->tenant_id) {
            return back()
                ->withErrors(['username' => 'Användaren saknar en tilldelad butik.'])
                ->onlyInput('username');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('tenant_id');

        if ($user->is_admin) {
            return redirect()->route('tenants.select');
        }

        $request->session()->put('tenant_id', $user->tenant_id);

        return redirect()->route('statistics.index');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
