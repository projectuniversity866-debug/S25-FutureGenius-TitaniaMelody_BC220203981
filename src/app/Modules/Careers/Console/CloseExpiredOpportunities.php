<?php

namespace App\Modules\Careers\Console;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Notifications\OpportunityClosed;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Daily (routes/console.php): opportunities whose deadline has passed are
 * taken off the board, logged, and their posters told how many applications
 * are waiting. Re-publishing with a new deadline opens them again.
 */
class CloseExpiredOpportunities extends Command
{
    protected $signature = 'careers:close-expired';

    protected $description = 'Close opportunities whose application deadline has passed';

    public function handle(ActivityLogger $activity): int
    {
        $closed = 0;

        JobPost::query()->with('user')->withCount('applications')
            ->where('is_active', true)
            ->whereDate('deadline', '<', today())
            ->each(function (JobPost $job) use ($activity, &$closed) {
                $job->update(['is_active' => false, 'closed_at' => now()]);
                $activity->record('opportunity.closed', $job, 'Closed “'.$job->title.'” at its deadline',
                    ['deadline' => $job->deadline->toDateString(), 'applications' => $job->applications_count]);
                $job->user->notify(new OpportunityClosed($job, $job->applications_count));
                $closed++;
            });

        $this->info("Closed {$closed} expired ".($closed === 1 ? 'opportunity' : 'opportunities').'.');

        return self::SUCCESS;
    }
}
