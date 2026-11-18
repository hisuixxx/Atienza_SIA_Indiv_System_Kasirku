<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if account is active
        if (!$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Please contact support.',
            ]);
        }

        $request->session()->regenerate();

        // Record login audit log
        AuditLog::record('user.login', 'auth', $user->id, null, ['role' => $user->role], $user->id);

        $targetUrl = $request->input('redirect');

        // Role-based redirection
        if ($user->isAdmin()) {
            return redirect()->intended($targetUrl ?: route('admin.dashboard'))->with('success', 'Welcome back, Administrator!');
        }

        if ($user->isStaff()) {
            return redirect()->intended($targetUrl ?: route('staff.dashboard'))->with('success', 'Welcome back, Barista / Staff!');
        }

        return redirect()->intended($targetUrl ?: route('menu'))->with('success', 'Welcome back to BrewSync, ' . $user->name . '!');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        if ($userId) {
            AuditLog::record('user.logout', 'auth', $userId, null, null, $userId);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
