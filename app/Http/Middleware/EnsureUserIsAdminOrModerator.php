<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || (! auth()->user()->isAdmin() && ! auth()->user()->isModerator())) {
            abort(403);
        }

        return $next($request);
    }
}
