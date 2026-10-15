<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials + ['is_super_admin' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।']);
        }

        $request->session()->regenerate();

        return \App\Support\TwoFactor::intercept($request, 'web', Auth::guard('web')->user(), $request->boolean('remember'), route('admin.dashboard'), 'admin.two-factor.challenge')
            ?? redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        if ($request->session()->pull('impersonating')) {
            Auth::guard('branch')->logout();
        }
        Auth::guard('web')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
