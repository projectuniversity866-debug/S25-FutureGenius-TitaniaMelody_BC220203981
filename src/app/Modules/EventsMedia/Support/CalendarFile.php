<?php

namespace App\Modules\EventsMedia\Support;

use App\Modules\EventsMedia\Models\Event;
use Illuminate\Support\Str;

/**
 * iCalendar (.ics, RFC 5545) for one event, so it can be added to Google
 * Calendar, Outlook or Apple Calendar (roadmap 3.8). Times are written in
 * UTC; calendar apps show them in the reader's own zone.
 */
class CalendarFile
{
    /** @param  bool  $withJoinLink  only for people allowed to see the meeting link */
    public static function make(Event $event, bool $withJoinLink): string
    {
        $url = route('events.show', $event);
        $description = $event->summary."\n\n".$url;
        if ($withJoinLink && $event->meeting_url) {
            $description .= "\n\nJoin: ".$event->meeting_url.($event->meeting_notes ? "\n".$event->meeting_notes : '');
        }
        $location = $event->venue ?? ($event->isOnline() ? ($withJoinLink && $event->meeting_url ? $event->meeting_url : 'Online') : '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AQDesk//Events//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'aqdesk'),
            'DTSTAMP:'.self::time(now()),
            'DTSTART:'.self::time($event->starts_at),
            'DTEND:'.self::time($event->ends_at),
            // Changes (new time, cancellation) replace the copy already in people's calendars.
            'SEQUENCE:'.max(0, $event->updated_at?->timestamp - $event->created_at?->timestamp),
            'STATUS:'.($event->status === 'cancelled' ? 'CANCELLED' : 'CONFIRMED'),
            'SUMMARY:'.self::text($event->title),
            'DESCRIPTION:'.self::text($description),
            'LOCATION:'.self::text($location),
            'URL:'.$url,
            'ORGANIZER;CN='.self::text($event->organizer?->name ?? 'AQDesk').':mailto:'.config('mail.from.address'),
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::text($event->title),
            'TRIGGER:-PT1H',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    /** A Google Calendar "add event" link (no joining link: it is a public URL). */
    public static function googleUrl(Event $event): string
    {
        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => $event->title,
            'dates'    => self::time($event->starts_at).'/'.self::time($event->ends_at),
            'details'  => $event->summary."\n\n".route('events.show', $event),
            'location' => $event->venue ?? ($event->isOnline() ? 'Online' : ''),
        ]);
    }

    public static function filename(Event $event): string
    {
        return Str::limit($event->slug, 60, '').'.ics';
    }

    private static function time($time): string
    {
        return $time->copy()->utc()->format('Ymd\THis\Z');
    }

    /** Escape commas, semicolons, backslashes and new lines (RFC 5545 §3.3.11). */
    private static function text(string $value): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }

    /** Lines longer than 75 bytes continue on the next line after a space, without splitting a character. */
    private static function fold(string $line): string
    {
        $out = '';
        $limit = 75;
        while (strlen($line) > $limit) {
            $cut = $limit;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
            $limit = 74;   // the leading space counts
        }

        return $out.$line;
    }
}
