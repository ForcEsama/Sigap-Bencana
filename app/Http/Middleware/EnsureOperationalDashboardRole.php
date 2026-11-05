<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperationalDashboardRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = $user === null ? null : UserRole::tryFrom($user->role);

        abort_unless(
            $user !== null && $user->is_active && $role?->isOperational(),
            403,
        );

        return $next($request);
    }
}
