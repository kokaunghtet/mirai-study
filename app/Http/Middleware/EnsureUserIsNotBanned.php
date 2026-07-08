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
        'profile.edit',
        'profile.update',
        'profile.preferences',
        'profile.username-available',
        'settings.index',
        'settings.update',
        'settings.theme-mode',
        'settings.two-factor',
        'notifications.index',
        'notifications.read',
        'notifications.read-all',
        'notifications.destroy',
        'notifications.destroy-all',
        'posts.like',
        'comments.like',
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

        // Permanently banned: force logout so they must use the login-page appeal flow.
        // Stash ban_appeal AFTER regenerating so it survives the session invalidation.
        if ($user->isBanned()) {
            $ban = $user->activeBan();
            $banAppealData = [
                'user_id' => $user->id,
                'ban_reason' => $ban?->reason,
                'ban_type' => $ban?->type,
                'has_open_appeal' => $ban?->hasOpenAppeal() ?? false,
            ];
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('ban_appeal', $banAppealData);

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
