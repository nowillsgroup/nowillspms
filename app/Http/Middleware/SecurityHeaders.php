<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Per-request CSP nonce for inline scripts (no 'unsafe-inline' for scripts).
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        view()->share('cspNonce', $nonce);

        $response = $next($request);
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->has('Referrer-Policy') || $h->set('Referrer-Policy', 'strict-origin-when-cross-origin'); // tokenised pages (guest portal) set no-referrer
        $h->has('Permissions-Policy') || $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()'); // v7.2: the lobby kiosk allows its own camera (ID photo)
        $h->set('X-XSS-Protection', '0');
        // a route may set a stricter / specific policy of its own (v7.1: the TV portal allows streaming media and the weather API)
        // script-src uses the per-request nonce + 'strict-dynamic' (modern browsers
        // ignore 'unsafe-inline' when a nonce is present). style-src keeps
        // 'unsafe-inline' for the print/table styles — styles are not executable.
        $h->has('Content-Security-Policy') || $h->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'; font-src 'self' data:; frame-ancestors ".config('pms.frame_ancestors', "'self'")."; base-uri 'self'; form-action 'self'");
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if ($request->user()) {
            $h->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
