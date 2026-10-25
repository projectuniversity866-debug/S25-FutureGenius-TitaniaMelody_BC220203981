<?php

namespace App\Modules\Careers\Policies;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Identity\Models\User;

class JobApplicationPolicy
{
    /** The opportunity's poster, or a moderator, moves an application on. */
    public function updateStatus(User $user, JobApplication $application): bool
    {
        return $user->id === $application->jobPost->user_id || $user->canModerate();
    }

    /** The applicant, the opportunity's poster, or a moderator may open the CV. */
    public function viewResume(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id || $this->updateStatus($user, $application);
    }

    /** Only the applicant withdraws their own application. */
    public function delete(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id;
    }
}
