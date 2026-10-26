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
