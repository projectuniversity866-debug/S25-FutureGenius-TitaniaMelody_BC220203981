<?php

namespace App\Modules\Careers\Notifications;

use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Platform\Notifications\AppNotification;

/** A new opportunity matching one of the member's job alerts. */
class MatchingOpportunity extends AppNotification
{
    public function __construct(private JobPost $job, private JobAlert $alert) {}

    protected function title(): string
    {
        return 'New '.mb_strtolower($this->job->typeLabel()).' for your alert';
    }

    protected function body(): string
    {
        return '“'.$this->job->title.'” at '.$this->job->company.' matches your alert: '.$this->alert->describe().'.';
    }

    protected function url(): string
    {
        return route('jobs.show', $this->job);
    }

    protected function category(): string
    {
        return 'alerts';
    }

    protected function icon(): string
    {
        return 'bell-fill';
    }
}
