<?php

namespace App\Modules\Consulting\Notifications;

use App\Modules\Consulting\Models\Consultation;
use App\Modules\Platform\Notifications\AppNotification;

/**
 * A session changed for one side of it (roadmap 3.12): booked (to the
 * client: confirmation; to the consultant: a new booking) or cancelled by
 * the other side.
 *
 * Keeps the wording, not the model: when a consultant deletes their account
 * the session is gone before the queue worker sends this (roadmap 5.1).
 */
class ConsultationUpdate extends AppNotification
{
    private string $topic;

    private string $when;

    private ?string $reason;

    private string $link;

    /** @param  string  $what  confirmed | booked | cancelled */
    public function __construct(Consultation $consultation, private string $what, private string $otherName)
    {
        $this->topic = $consultation->topic;
        $this->when = $consultation->whenLabel();
        $this->reason = $consultation->cancel_reason;
        $this->link = route('consulting.sessions.show', $consultation);
    }

    protected function title(): string
    {
        return [
            'confirmed' => 'Session booked with '.$this->otherName,
            'booked'    => 'New booking: '.$this->topic,
            'cancelled' => 'Session cancelled by '.$this->otherName,
        ][$this->what];
    }

    protected function body(): string
    {
        return match ($this->what) {
            'confirmed' => 'Your session “'.$this->topic.'” is on '.$this->when.'. The joining link is on the session page.',
            'booked'    => $this->otherName.' booked “'.$this->topic.'” on '.$this->when.'.',
            'cancelled' => 'The session “'.$this->topic.'” on '.$this->when.' will not take place.'
                .($this->reason ? ' Reason: '.$this->reason : ''),
        };
    }

    protected function url(): string
    {
        return $this->link;
    }

    protected function icon(): string
    {
        return $this->what === 'cancelled' ? 'calendar-x' : 'calendar-check';
    }

    protected function category(): string
    {
        return 'consulting';
    }
}
