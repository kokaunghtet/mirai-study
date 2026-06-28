<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Routes a banned/suspended user may still access.
     * They keep their session so they can reach the appeal form.
     */
    private const ALLOWED_ROUTES = [
        'banned',
        'appeal.create',
        'appeal.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Allow whitelisted routes regardless of ban status
        $routeName = $request->route()?->getName();
        if (in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // isBannedNow() lazily auto-lifts expired temp bans as a side-effect
        if (! $user->isBannedNow()) {
            return $next($request);
        }

        // Permanently banned: force logout so they must use the login-page appeal flow
        if ($user->isBanned()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        // Temporarily suspended: allow read-only browsing, block writes
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Write action from a suspended user — trigger the appeal modal client-side
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['banned' => true], 403);
        }

        return redirect()->back()->with('show_appeal_modal', true);
    }
}
