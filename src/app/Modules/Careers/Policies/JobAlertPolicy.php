<?php

namespace App\Modules\Careers\Policies;

use App\Modules\Careers\Models\JobAlert;
use App\Modules\Identity\Models\User;

class JobAlertPolicy
{
    /** Alerts are private: only their owner removes one. */
    public function delete(User $user, JobAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }
}
