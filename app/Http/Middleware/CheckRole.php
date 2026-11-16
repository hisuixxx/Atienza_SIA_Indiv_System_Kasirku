<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles  One or more allowed role strings.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            // Not authenticated – let auth middleware handle it.
            return $next($request);
        }
        $allowed = array_map('strtolower', $roles);
        $userRole = strtolower($user->role);
        if (! in_array($userRole, $allowed, true)) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}
