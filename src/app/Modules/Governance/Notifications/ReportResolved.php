<?php

namespace App\Modules\Governance\Notifications;

use App\Modules\Platform\Notifications\AppNotification;

/** Thanks a reporter and tells them what was decided. */
class ReportResolved extends AppNotification
{
    public function __construct(private string $label, private bool $removed) {}

    protected function title(): string
    {
        return 'Thanks for your report';
    }

    protected function body(): string
    {
        return $this->removed
            ? 'A moderator reviewed '.$this->label.' and removed it.'
            : 'A moderator reviewed '.$this->label.' and found it within the community guidelines.';
    }

    protected function url(): string
    {
        return route('guidelines');
    }

    protected function icon(): string
    {
        return 'flag';
    }
}
