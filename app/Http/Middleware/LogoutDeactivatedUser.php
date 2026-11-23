<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account that an administrator deactivated while the person was still signed in.
 */
class LogoutDeactivatedUser
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if (! $request->expectsJson()) {
                return redirect()->route($request->is('admin', 'admin/*') ? 'admin.login' : 'home')->with('status', 'Ce compte est désactivé. Contactez-nous si besoin.');
            }

            abort(401, 'Compte désactivé.');
        }

        return $next($request);
    }
}
