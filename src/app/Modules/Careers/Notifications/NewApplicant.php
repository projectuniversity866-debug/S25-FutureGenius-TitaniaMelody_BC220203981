<?php

namespace App\Modules\Careers\Notifications;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells the poster that someone applied to their opportunity. */
class NewApplicant extends AppNotification
{
    public function __construct(private JobApplication $application) {}

    protected function title(): string
    {
        return 'New applicant';
    }

    protected function body(): string
    {
        return $this->application->user->name.' applied to “'.$this->application->jobPost->title.'”.';
    }

    protected function url(): string
    {
        return route('jobs.applicants', $this->application->jobPost);
    }

    protected function category(): string
    {
        return 'applications';
    }

    protected function icon(): string
    {
        return 'person-plus';
    }
}
