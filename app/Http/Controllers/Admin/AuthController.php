<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Slows down password guessing on one account even when the attacker rotates IP addresses.
        $lockKey = 'admin-login:'.Str::lower($credentials['email']);

        if (RateLimiter::tooManyAttempts($lockKey, 8)) {
            throw ValidationException::withMessages(['email' => 'Trop de tentatives. Réessayez dans '.ceil(RateLimiter::availableIn($lockKey) / 60).' minute(s).']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($lockKey, 900);

            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        RateLimiter::clear($lockKey);

        $user = $request->user();

        if (! $user->canAccessAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Ce compte n’a pas accès à l’administration.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
