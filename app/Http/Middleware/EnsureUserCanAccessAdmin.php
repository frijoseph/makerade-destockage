<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets only active admin and manager accounts into the back office.
 * Pass "admin" as a parameter to restrict a route to administrators.
 */
class EnsureUserCanAccessAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            if ($request->expectsJson()) {
                abort(401, 'Authentification requise.');
            }

            Auth::logout();

            return redirect()->guest(route('admin.login'));
        }

        if ($level === 'admin' && ! $user->isAdmin()) {
            abort(403, 'Réservé aux administrateurs.');
        }

        return $next($request);
    }
}
