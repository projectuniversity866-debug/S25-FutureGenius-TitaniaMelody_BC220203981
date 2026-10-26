<?php

namespace App\Modules\EventsMedia\Notifications;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\Platform\Notifications\AppNotification;

/** Within a day of the start: the organizer's own heads-up with the head count (roadmap 3.9). */
class OrganizerReminder extends AppNotification
{
    public function __construct(private Event $event, private int $confirmed, private int $waitlisted) {}

    protected function title(): string
    {
        return 'Your event is coming up: '.$this->event->title;
    }

    protected function body(): string
    {
        return $this->confirmed.' '.($this->confirmed === 1 ? 'person has' : 'people have').' a seat'
            .($this->waitlisted ? ' and '.$this->waitlisted.' '.($this->waitlisted === 1 ? 'is' : 'are').' waiting' : '')
            .'. '.$this->event->whenLabel().'.'
            .($this->event->isOnline() && ! $this->event->meeting_url ? ' There is no joining link yet — add one so attendees can join.' : '');
    }

    protected function url(): string
    {
        return route('events.attendees', $this->event);
    }

    protected function icon(): string
    {
        return 'alarm';
    }

    protected function category(): string
    {
        return 'events';
    }
}
