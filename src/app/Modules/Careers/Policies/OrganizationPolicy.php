<?php

namespace App\Modules\Careers\Policies;

use App\Modules\Careers\Models\Organization;
use App\Modules\Identity\Models\User;

class OrganizationPolicy
{
    /** Whoever may publish opportunities may set up an organization profile. */
    public function create(User $user): bool
    {
        return $user->canPostJobs();
    }

    /** The owner, or a moderator, manages a profile. */
    public function update(User $user, Organization $organization): bool
    {
        return $user->id === $organization->user_id || $user->canModerate();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }
}
