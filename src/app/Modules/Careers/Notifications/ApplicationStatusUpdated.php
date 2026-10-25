<?php

namespace App\Modules\Careers\Notifications;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells an applicant that their application moved to a new status. */
class ApplicationStatusUpdated extends AppNotification
{
    public function __construct(private JobApplication $application) {}

    protected function title(): string
    {
        return 'Application '.$this->application->status;
    }

    protected function body(): string
    {
        return 'Your application to “'.$this->application->jobPost->title.'” is now '.$this->application->status.'.';
    }

    protected function url(): string
    {
        return route('applications.index');
    }

    protected function category(): string
    {
        return 'applications';
    }

    protected function icon(): string
    {
        return 'send-check';
    }
}
