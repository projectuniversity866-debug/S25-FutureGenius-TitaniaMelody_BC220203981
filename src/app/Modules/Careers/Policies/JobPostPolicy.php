<?php

namespace App\Modules\Careers\Policies;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

class JobPostPolicy
{
    /** Administrators and Experts publish opportunities. */
    public function create(User $user): bool
    {
        return $user->canPostJobs();
    }

    /** The poster, or a moderator, manages an opportunity. */
    public function update(User $user, JobPost $job): bool
    {
        return $user->id === $job->user_id || $user->canModerate();
    }

    public function delete(User $user, JobPost $job): bool
    {
        return $this->update($user, $job);
    }

    public function viewApplicants(User $user, JobPost $job): bool
    {
        return $this->update($user, $job);
    }

    /**
     * Anyone signed in may apply to an open opportunity, except its poster.
     * Applying twice is a business rule checked by ApplicationService.
     */
    public function apply(User $user, JobPost $job): Response
    {
        if (! $user->hasPermission('opportunities.apply')) {
            return Response::deny('Your role cannot apply to opportunities.');
        }

        if (! $job->isOpen()) {
            return Response::denyAsNotFound('This opportunity is no longer accepting applications.');
        }

        if ($user->id === $job->user_id) {
            return Response::deny('You cannot apply to your own opportunity.');
        }

        return Response::allow();
    }
}
