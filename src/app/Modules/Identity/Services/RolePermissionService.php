<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Support\PermissionCatalog;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Validation\ValidationException;

class RolePermissionService
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * Replace the role's permissions with the given slugs.
     *
     * @param  array<int, string>  $slugs
     *
     * @throws ValidationException when the change would lock administrators out
     */
    public function sync(Role $role, array $slugs): void
    {
        if ($role->slug === Role::ADMIN && array_diff(PermissionCatalog::LOCKED, $slugs)) {
            throw ValidationException::withMessages([
                'permissions' => 'Administrators must keep “'.PermissionCatalog::all()['users.manage']['name'].'”, or nobody could manage roles any more.',
            ]);
        }

        $before = $role->permissions()->pluck('slug')->all();
        $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));
        $after = $role->permissions()->pluck('slug')->all();

        $added   = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added || $removed) {
            $this->activity->record('role.permissions_changed', $role,
                'Changed the permissions of '.$role->name.' ('.count($added).' added, '.count($removed).' removed)',
                ['added' => $added, 'removed' => $removed]);
        }
    }
}
