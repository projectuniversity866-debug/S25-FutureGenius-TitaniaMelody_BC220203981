<?php

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Public profiles are open to everyone; "members only" profiles are
     * hidden from guests (as if they did not exist).
     */
    public function viewProfile(?User $viewer, User $user): Response
    {
        return $user->setting('profile_visibility') === 'members' && ! $viewer
            ? Response::denyAsNotFound()
            : Response::allow();
    }
}
