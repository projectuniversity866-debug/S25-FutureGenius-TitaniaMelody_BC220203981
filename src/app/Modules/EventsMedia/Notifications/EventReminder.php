<?php

namespace App\Modules\EventsMedia\Notifications;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Support\CalendarFile;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Automated reminder to someone with a seat (roadmap 3.9): the day before
 * and an hour before. Online events include the joining link.
 */
class EventReminder extends AppNotification
{
    /** @param  string  $when  day | hour */
    public function __construct(private Event $event, private string $when) {}

    protected function title(): string
    {
        return ($this->when === 'hour' ? 'Starting soon: ' : 'Reminder: ').$this->event->title;
    }

    protected function body(): string
    {
        return '“'.$this->event->title.'” '.($this->when === 'hour' ? 'starts within the hour' : 'is coming up').' — '
            .$this->event->whenLabel().'. '
            .($this->event->venue ? 'Venue: '.$this->event->venue.'. ' : '')
            .($this->event->isOnline() && $this->event->meeting_url ? 'Join online from the event page.' : '');
    }

    protected function url(): string
    {
        return route('events.show', $this->event);
    }

    protected function icon(): string
    {
        return 'alarm';
    }

    protected function category(): string
    {
        return 'events';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);
        if ($this->event->isOnline() && $this->event->meeting_url) {
            $mail->line('Joining link ('.$this->event->meetingProvider().'): '.$this->event->meeting_url);
            if ($this->event->meeting_notes) {
                $mail->line($this->event->meeting_notes);
            }
        }
        $mail->line('Cannot come any more? Cancel on the event page so your seat goes to someone on the waitlist.');

        return $mail->attachData(CalendarFile::make($this->event, true), CalendarFile::filename($this->event), ['mime' => 'text/calendar']);
    }
}
