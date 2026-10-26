<?php

namespace App\Modules\Careers\Listeners;

use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Events\ApplicationSubmitted;
use App\Modules\Careers\Notifications\ApplicationStatusUpdated;
use App\Modules\Careers\Notifications\NewApplicant;

class SendApplicationNotifications
{
    public function handleSubmitted(ApplicationSubmitted $event): void
    {
        $application = $event->application->loadMissing('jobPost.user', 'user');

        $application->jobPost->user->notify(new NewApplicant($application));
    }

    public function handleStatusChanged(ApplicationStatusChanged $event): void
    {
        $application = $event->application->loadMissing('jobPost', 'user');

        $application->user->notify(new ApplicationStatusUpdated($application, $event->message));
    }
}
