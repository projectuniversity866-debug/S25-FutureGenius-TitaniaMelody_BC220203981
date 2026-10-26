<?php

namespace App\Modules\Consulting\Policies;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** Who may see and run a consultant profile (FR6, roadmap 3.11). */
class ConsultantProfilePolicy
{
    /** Public while the consultant accepts bookings and still may offer consulting; otherwise only they and moderators see it. */
    public function view(?User $user, ConsultantProfile $profile): Response
    {
        $public = $profile->is_accepting && $profile->user?->is_active && $profile->user->hasPermission('consulting.offer');

        return $public || ($user && ($user->id === $profile->user_id || $user->canModerate()))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Having your own profile and hours. */
    public function offer(User $user): bool
    {
        return $user->hasPermission('consulting.offer');
    }
}
