<?php

namespace App\Modules\Governance\Notifications;

use App\Modules\Governance\Models\Report;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells moderators that something was reported. */
class NewReport extends AppNotification
{
    public function __construct(private Report $report, private string $label) {}

    protected function title(): string
    {
        return 'New report: '.$this->report->reasonLabel();
    }

    protected function body(): string
    {
        return $this->label.' was reported and is waiting in the moderation queue.';
    }

    protected function url(): string
    {
        return route('admin.reports.show', $this->report);
    }

    protected function icon(): string
    {
        return 'flag';
    }
}
