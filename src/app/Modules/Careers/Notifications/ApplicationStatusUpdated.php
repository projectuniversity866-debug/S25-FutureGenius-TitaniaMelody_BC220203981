<?php

namespace App\Modules\Careers\Notifications;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Support\Str;

/** Tells an applicant that their application moved to a new status. */
class ApplicationStatusUpdated extends AppNotification
{
    public function __construct(private JobApplication $application, private ?string $message = null) {}

    protected function title(): string
    {
        return 'Application '.$this->application->status;
    }

    protected function body(): string
    {
        $body = 'Your application to “'.$this->application->jobPost->title.'” is now '.$this->application->status.'.';

        return $this->message ? $body.' Message: “'.Str::limit($this->message, 120).'”' : $body;
    }

    protected function url(): string
    {
        return route('applications.show', $this->application);
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
