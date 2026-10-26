<?php

namespace App\Modules\EventsMedia\Notifications;

use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Support\CalendarFile;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells a member where they stand: registered, on the waitlist, moved up
 * from the waitlist, or removed by the organizer.
 */
class RegistrationUpdate extends AppNotification
{
    /** @param  string  $what  confirmed | waitlisted | promoted | removed */
    public function __construct(private EventRegistration $registration, private string $what) {}

    protected function title(): string
    {
        return [
            'confirmed'  => 'You are registered',
            'waitlisted' => 'You are on the waitlist',
            'promoted'   => 'A seat opened up — you are in',
            'removed'    => 'Your registration was cancelled',
        ][$this->what];
    }

    protected function body(): string
    {
        $event = $this->registration->event;

        return match ($this->what) {
            'confirmed'  => 'Your seat for “'.$event->title.'” ('.$event->whenLabel().') is confirmed.',
            'waitlisted' => '“'.$event->title.'” is full. You are number '.$this->registration->waitlistPosition()
                .' on the waitlist; we will tell you if a seat opens up.',
            'promoted'   => 'You moved up from the waitlist and now have a seat for “'.$event->title.'” ('.$event->whenLabel().').',
            'removed'    => 'The organizer cancelled your registration for “'.$event->title.'”.',
        };
    }

    protected function url(): string
    {
        return route('events.show', $this->registration->event);
    }

    protected function icon(): string
    {
        return ['confirmed' => 'calendar-check', 'waitlisted' => 'hourglass-split', 'promoted' => 'calendar-check', 'removed' => 'calendar-x'][$this->what];
    }

    protected function category(): string
    {
        return 'events';
    }

    /** A seat comes with the calendar file, joining link included (roadmap 3.8). */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);
        $event = $this->registration->event;

        return in_array($this->what, ['confirmed', 'promoted'], true)
            ? $mail->line('Add it to your calendar with the attached file.')
                ->attachData(CalendarFile::make($event, true), CalendarFile::filename($event), ['mime' => 'text/calendar'])
            : $mail;
    }
}
