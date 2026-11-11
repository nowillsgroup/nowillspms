<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Staff session idle timeout (SESSION_IDLE_TIMEOUT minutes, default 30).
 * Partner/organiser portals already have their own idle timeout; this covers
 * the main staff session. Stateless/API requests are skipped.
 */
class CheckIdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->is('api/*')) {
            $timeout = (int) config('session.idle_timeout', 30);
            if ($timeout > 0) {
                $last = $request->session()->get('last_activity_at');
                $now = time();
                if ($last && ($now - (int) $last) > ($timeout * 60)) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('error', 'Your session timed out for inactivity. Please sign in again.');
                }
                $request->session()->put('last_activity_at', $now);
            }
        }

        return $next($request);
    }
}
