<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::with('branches')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status !== null && $request->status !== '', function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->latest();

        $users = $query->paginate(15)->withQueryString();

        $stats = [
            'total'     => User::count(),
            'active'    => User::where('is_active', true)->count(),
            'inactive'  => User::where('is_active', false)->count(),
            'admin'     => User::where('role', User::ROLE_ADMIN)->count(),
            'staff'     => User::where('role', User::ROLE_STAFF)->count(),
            'kitchen'   => User::where('role', User::ROLE_KITCHEN)->count(),
            'customer'  => User::where('role', User::ROLE_CUSTOMER)->count(),
        ];

        return Inertia::render('admin/users/index', [
            'users'    => $users,
            'stats'    => $stats,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'filters'  => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:500'],
            'password'   => ['required', Password::defaults()],
            'role'       => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_KITCHEN, User::ROLE_CUSTOMER])],
            'is_active'  => ['boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'address'           => $validated['address'] ?? null,
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'is_active'         => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        // Only staff, kitchen, and admin can be assigned to branches
        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_KITCHEN])) {
            $user->branches()->sync($validated['branch_ids'] ?? []);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        // Prevent admin from demoting the last remaining admin
        if ($user->role === User::ROLE_ADMIN && $request->role !== User::ROLE_ADMIN) {
            $adminCount = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot change role of the last active administrator.');
            }
        }

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:500'],
            'password'   => ['nullable', Password::defaults()],
            'role'       => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_KITCHEN, User::ROLE_CUSTOMER])],
            'is_active'  => ['boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user->update([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'address'  => $validated['address'] ?? null,
            'role'     => $validated['role'],
            'is_active'=> $validated['is_active'] ?? $user->is_active,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_KITCHEN])) {
            $user->branches()->sync($validated['branch_ids'] ?? []);
        } else {
            $user->branches()->detach();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeAdmin();

        // Prevent deactivating the last active admin
        if ($user->role === User::ROLE_ADMIN && $user->is_active) {
            $activeAdmins = User::where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($activeAdmins === 0) {
                return back()->with('error', 'Cannot deactivate the last active administrator.');
            }
        }

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === User::ROLE_ADMIN) {
            $adminCount = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot delete the last active administrator.');
            }
        }

        // Soft delete (historical records preserved)
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user() || auth()->user()->role !== User::ROLE_ADMIN) {
            abort(403, 'Only administrators can manage users.');
        }
    }
}

