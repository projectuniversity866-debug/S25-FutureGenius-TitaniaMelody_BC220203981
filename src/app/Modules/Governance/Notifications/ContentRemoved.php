<?php

namespace App\Modules\Governance\Notifications;

use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Support\Str;

/** Tells an author that a moderator removed their content, and why. */
class ContentRemoved extends AppNotification
{
    public function __construct(private string $label, private string $reason, private ?string $note) {}

    protected function title(): string
    {
        return 'Your content was removed';
    }

    protected function body(): string
    {
        $body = $this->label.' was removed by a moderator: '.$this->reason.'.';

        return $this->note ? $body.' Note: “'.Str::limit($this->note, 160).'”' : $body;
    }

    protected function url(): string
    {
        return route('guidelines');
    }

    protected function icon(): string
    {
        return 'shield-exclamation';
    }
}
