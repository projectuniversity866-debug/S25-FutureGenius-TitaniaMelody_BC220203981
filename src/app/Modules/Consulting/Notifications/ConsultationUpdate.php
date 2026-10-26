<?php

namespace App\Modules\Consulting\Notifications;

use App\Modules\Consulting\Models\Consultation;
use App\Modules\Platform\Notifications\AppNotification;

/**
 * A session changed for one side of it (roadmap 3.12): booked (to the
 * client: confirmation; to the consultant: a new booking) or cancelled by
 * the other side.
 */
class ConsultationUpdate extends AppNotification
{
    /** @param  string  $what  confirmed | booked | cancelled */
    public function __construct(private Consultation $consultation, private string $what, private string $otherName) {}

    protected function title(): string
    {
        return [
            'confirmed' => 'Session booked with '.$this->otherName,
            'booked'    => 'New booking: '.$this->consultation->topic,
            'cancelled' => 'Session cancelled by '.$this->otherName,
        ][$this->what];
    }

    protected function body(): string
    {
        $when = $this->consultation->whenLabel();

        return match ($this->what) {
            'confirmed' => 'Your session “'.$this->consultation->topic.'” is on '.$when.'. The joining link is on the session page.',
            'booked'    => $this->otherName.' booked “'.$this->consultation->topic.'” on '.$when.'.',
            'cancelled' => 'The session “'.$this->consultation->topic.'” on '.$when.' will not take place.'
                .($this->consultation->cancel_reason ? ' Reason: '.$this->consultation->cancel_reason : ''),
        };
    }

    protected function url(): string
    {
        return route('consulting.sessions.show', $this->consultation);
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
