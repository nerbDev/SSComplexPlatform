<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminType
{
    /**
     * Usage in routes: ->middleware(['role:admin', 'admin_type:operation_building'])
     * Always pair with EnsureRole (role:admin) first — this only checks the
     * specialization, not whether the user is an admin at all.
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin' || !in_array($user->admin_type, $types, true)) {
            abort(403, 'This section isn\'t part of your admin focus area.');
        }

        return $next($request);
    }
}