<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser hardening headers on every web response: content security policy, framing, MIME sniffing,
 * referrer, device permissions, HSTS over HTTPS, and no caching of private pages.
 */
class SecurityHeaders
{
    /**
     * Paths whose responses depend on the signed-in person (never stored by browsers or proxies).
     *
     * @var list<string>
     */
    private const PRIVATE_PATHS = ['admin', 'admin/*', 'account', 'account/*', 'commande/*', 'wishlist'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->remove('X-Powered-By');

        if (config('shop.security.csp')) {
            $headers->set('Content-Security-Policy', self::policy());
        }

        if ($request->isSecure() && config('shop.security.hsts')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is(...self::PRIVATE_PATHS)) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /**
     * Same-origin by default; the only third parties are the CDN holding SweetAlert2 / Tom Select and Google Fonts.
     * Inline scripts and styles stay allowed because the pages embed their configuration inline.
     */
    public static function policy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
