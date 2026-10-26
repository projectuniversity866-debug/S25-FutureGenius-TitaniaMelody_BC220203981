<?php

namespace App\Modules\Knowledge\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\ExpertVerification;

class ExpertVerificationPolicy
{
    /** Administrators (experts.verify) decide requests. */
    public function review(User $user, ExpertVerification $request): bool
    {
        return $user->can('experts.verify');
    }

    /** The evidence file: its owner or a reviewer. */
    public function viewDocument(User $user, ExpertVerification $request): bool
    {
        return $user->id === $request->user_id || $this->review($user, $request);
    }
}
