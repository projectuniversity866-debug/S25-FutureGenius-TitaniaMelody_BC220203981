<?php

namespace App\Modules\EventsMedia\Notifications;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\Platform\Notifications\AppNotification;

/** The organizer cancelled an event you registered for. Always emailed: people may be travelling to it. */
class EventCancelled extends AppNotification
{
    public function __construct(private Event $event) {}

    protected function title(): string
    {
        return 'Event cancelled: '.$this->event->title;
    }

    protected function body(): string
    {
        return '“'.$this->event->title.'” ('.$this->event->whenLabel().') will not take place.'
            .($this->event->cancel_reason ? ' Reason: '.$this->event->cancel_reason : '');
    }

    protected function url(): string
    {
        return route('events.show', $this->event);
    }

    protected function icon(): string
    {
        return 'calendar-x';
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }
}
