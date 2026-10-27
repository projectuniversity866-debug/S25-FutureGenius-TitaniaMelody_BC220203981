<?php

use App\Modules\Platform\Models\ActivityLog;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| On the server, one cron entry runs these:
|
|   * * * * * cd /path/to/src && php artisan schedule:run >> /dev/null 2>&1
|
| Locally: `php artisan schedule:list` shows them, `php artisan schedule:work`
| runs them in the foreground.
*/

// Audit-trail retention (ActivityLog::RETENTION_DAYS).
Schedule::command('model:prune', ['--model' => ActivityLog::class])->daily();

// Remove failed queue jobs older than a week.
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();

// Take opportunities off the board once their deadline has passed, and tell
// their posters (roadmap 1.15).
Schedule::command('careers:close-expired')->dailyAt('00:15');

// Event reminders: the day before and an hour before, plus the organizer's
// head count (roadmap 3.9). Each reminder is sent once, so overlap is safe.
Schedule::command('events:send-reminders')->everyFifteenMinutes()->withoutOverlapping();

// Newsletter campaigns whose scheduled time has come start sending (roadmap 3.16).
// Starting is claimed in the database, so overlap is safe.
Schedule::command('newsletter:send-due')->everyMinute()->withoutOverlapping();

// Queue worker for hosts without Supervisor, e.g. cPanel (roadmap 5.1).
// QUEUE_WORKER_IN_SCHEDULER=true turns it on; see docs/DEPLOYMENT.md.
Schedule::command('queue:work', ['--stop-when-empty', '--max-time' => 50, '--tries' => 3])
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.worker_in_scheduler'));
