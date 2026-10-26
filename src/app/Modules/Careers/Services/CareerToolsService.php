<?php

namespace App\Modules\Careers\Services;

use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Career tools (roadmap 1.15): saving opportunities for later and job
 * alerts. Saving twice, or removing what was never saved, changes nothing.
 */
class CareerToolsService
{
    public function save(User $user, JobPost $job): void
    {
        $job->savedBy()->syncWithoutDetaching([$user->id]);
    }

    public function unsave(User $user, JobPost $job): void
    {
        $job->savedBy()->detach($user->id);
    }

    /**
     * @param  array{keywords?: ?string, type?: ?string, work_mode?: ?string}  $criteria
     *
     * @throws ValidationException at the limit, or for an alert the member already has
     */
    public function createAlert(User $user, array $criteria): JobAlert
    {
        $criteria = [
            'keywords'  => filled($criteria['keywords'] ?? null) ? trim($criteria['keywords']) : null,
            'type'      => $criteria['type'] ?? null,
            'work_mode' => $criteria['work_mode'] ?? null,
        ];

        $alerts = JobAlert::ownedBy($user);
        if ((clone $alerts)->count() >= JobAlert::MAX_PER_USER) {
            throw ValidationException::withMessages(['alert' => 'You can keep up to '.JobAlert::MAX_PER_USER.' job alerts. Delete one first.']);
        }
        if ((clone $alerts)->where($criteria)->exists()) {
            throw ValidationException::withMessages(['alert' => 'You already have an alert for exactly this search.']);
        }

        $alert = new JobAlert($criteria);
        $alert->user()->associate($user)->save();

        return $alert;
    }

    public function deleteAlert(JobAlert $alert): void
    {
        $alert->delete();
    }
}
