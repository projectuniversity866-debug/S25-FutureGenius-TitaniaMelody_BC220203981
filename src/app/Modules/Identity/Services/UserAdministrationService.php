<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Validation\ValidationException;

/**
 * Administrator actions on other accounts. An administrator can never lock
 * themselves out by demoting or deactivating their own account. Every change
 * is written to the activity log.
 */
class UserAdministrationService
{
    public function __construct(private ActivityLogger $activity) {}

    /** @throws ValidationException when the administrator targets themselves */
    public function changeRole(User $admin, User $user, int $roleId): User
    {
        if ($user->is($admin)) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        $from = $user->roleName();
        $user->update(['role_id' => $roleId]);
        $to = Role::find($roleId)?->name;

        if ($from !== $to) {
            $this->activity->record('user.role_changed', $user, 'Changed '.$user->name.'’s role from '.$from.' to '.$to,
                ['from' => $from, 'to' => $to]);
        }

        return $user->load('role');
    }

    /** @throws ValidationException when the administrator targets themselves */
    public function toggleActive(User $admin, User $user): User
    {
        if ($user->is($admin)) {
            throw ValidationException::withMessages(['active' => 'You cannot deactivate your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $this->activity->record($user->is_active ? 'user.activated' : 'user.deactivated', $user,
            ($user->is_active ? 'Reactivated' : 'Deactivated').' the account of '.$user->name);

        return $user;
    }
}
