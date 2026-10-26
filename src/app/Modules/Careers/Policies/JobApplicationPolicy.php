<?php

namespace App\Modules\Careers\Policies;

use App\Modules\Careers\Models\ApplicationNote;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Identity\Models\User;

class JobApplicationPolicy
{
    /** The opportunity's poster, or a moderator, moves an application on. */
    public function updateStatus(User $user, JobApplication $application): bool
    {
        return $user->id === $application->jobPost->user_id || $user->canModerate();
    }

    /** The applicant sees their own application and its history; reviewers see it too. */
    public function view(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id || $this->updateStatus($user, $application);
    }

    /** Private notes are for reviewers only — never the applicant. */
    public function review(User $user, JobApplication $application): bool
    {
        return $this->updateStatus($user, $application);
    }

    /** A note is removed by whoever wrote it, or a moderator. */
    public function deleteNote(User $user, ApplicationNote $note): bool
    {
        return $user->id === $note->user_id || $user->canModerate();
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
