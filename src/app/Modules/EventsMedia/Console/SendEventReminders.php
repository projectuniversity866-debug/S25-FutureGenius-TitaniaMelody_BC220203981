<?php

namespace App\Modules\EventsMedia\Console;

use App\Modules\EventsMedia\Services\EventService;
use Illuminate\Console\Command;

/**
 * Every 15 minutes (routes/console.php): reminders to people with a seat,
 * the day before and an hour before, and the organizer's head count the
 * day before (roadmap 3.9). Safe to run as often as you like.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminders to attendees and organizers';

    public function handle(EventService $events): int
    {
        $sent = $events->sendReminders();

        $this->info("Sent {$sent['attendees']} attendee and {$sent['organizers']} organizer ".($sent['attendees'] + $sent['organizers'] === 1 ? 'reminder' : 'reminders').'.');

        return self::SUCCESS;
    }
}
