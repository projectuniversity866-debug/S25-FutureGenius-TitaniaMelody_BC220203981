<?php

namespace App\Modules\Identity\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Services\RolePermissionService;
use App\Modules\Identity\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The role × permission matrix (FR1 role-based access control). Behind
 * can:users.manage.
 */
class RolePermissionController extends Controller
{
    public function __construct(private RolePermissionService $service) {}

    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('id')->get();
        $permissions = Permission::orderBy('id')->get()->groupBy('group');

        return view('identity::admin.roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'locked' => PermissionCatalog::LOCKED,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'slug')],
        ]);

        $this->service->sync($role, $validated['permissions'] ?? []);

        return back()->with('status', 'Permissions for '.$role->name.' saved.');
    }
}
