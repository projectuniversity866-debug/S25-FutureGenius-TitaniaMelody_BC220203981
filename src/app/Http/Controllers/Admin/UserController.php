<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator-only user and role management.
 * Demonstrates the "Role-Based Access Control" half of FR1.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role')
            ->withCount(['questions', 'answers'])
            ->when($request->query('q'), function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->query('role'), function ($query, $slug) {
                $query->whereHas('role', fn ($q) => $q->where('slug', $slug));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /** Change a user's role. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->update($validated);

        return back()->with('status', $user->name.' is now a '.$user->fresh()->roleName().'.');
    }

    /** Activate / deactivate an account. */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['active' => 'You cannot deactivate your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->name.' has been '.($user->is_active ? 'activated' : 'deactivated').'.');
    }
}
