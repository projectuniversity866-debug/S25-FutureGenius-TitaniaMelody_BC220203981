<?php

namespace App\Modules\Identity\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Requests\UpdateUserRoleRequest;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator-only user and role management — the "role-based access
 * control" half of FR1. The whole controller is behind can:users.manage.
 */
class UserController extends Controller
{
    public function __construct(private UserAdministrationService $administration) {}

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

        return view('identity::admin.users.index', compact('users', 'roles'));
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $this->administration->changeRole($request->user(), $user, (int) $request->validated('role_id'));

        return back()->with('status', $user->name.' is now a '.$user->roleName().'.');
    }

    /** Activate / deactivate an account. */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->administration->toggleActive($request->user(), $user);

        return back()->with('status', $user->name.' has been '.($user->is_active ? 'activated' : 'deactivated').'.');
    }
}
