<?php

namespace App\Modules\Careers\Notifications;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Support\Str;

/** Tells a poster that the scheduler closed their opportunity at its deadline. */
class OpportunityClosed extends AppNotification
{
    public function __construct(private JobPost $job, private int $applications) {}

    protected function title(): string
    {
        return 'Opportunity closed at its deadline';
    }

    protected function body(): string
    {
        return '“'.$this->job->title.'” no longer accepts applications. '
            .$this->applications.' '.Str::plural('application', $this->applications).' to review.';
    }

    protected function url(): string
    {
        return route('jobs.applicants', $this->job);
    }

    protected function category(): string
    {
        return 'applications';
    }

    protected function icon(): string
    {
        return 'calendar-x';
    }
}
