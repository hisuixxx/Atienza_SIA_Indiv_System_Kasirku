<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        if (!$request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        $userRole = $request->user()->role;
        $mappedRoles = match ($userRole) {
            'administrator', 'admin' => ['admin', 'administrator'],
            'staff', 'cashier', 'kitchen_staff', 'inventory_staff', 'branch_manager' => ['staff', 'cashier', 'kitchen_staff', 'inventory_staff', 'branch_manager', $userRole],
            default => [$userRole],
        };

        if (empty(array_intersect($mappedRoles, $roles))) {
            abort(403, 'Unauthorized access. You do not have permission to view this resource.');
        }

        return $next($request);
    }
}
