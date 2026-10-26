<?php

namespace App\Modules\EventsMedia\Notifications;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Support\CalendarFile;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

/** The time, place or joining link of an event you registered for changed; the email carries the updated calendar file. */
class EventChanged extends AppNotification
{
    /** @param  array<int, string>  $what  e.g. ['time', 'joining link'] */
    public function __construct(private Event $event, private array $what, private bool $hasSeat) {}

    protected function title(): string
    {
        return 'Event updated: '.$this->event->title;
    }

    protected function body(): string
    {
        return 'The organizer changed the '.implode(' and ', $this->what).'. It is now '.$this->event->whenLabel()
            .($this->event->venue ? ', '.$this->event->venue : '').'.';
    }

    protected function url(): string
    {
        return route('events.show', $this->event);
    }

    protected function icon(): string
    {
        return 'calendar2-week';
    }

    protected function category(): string
    {
        return 'events';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return parent::toMail($notifiable)
            ->line('The attached calendar file replaces the one you saved before.')
            ->attachData(CalendarFile::make($this->event, $this->hasSeat), CalendarFile::filename($this->event), ['mime' => 'text/calendar']);
    }
}
