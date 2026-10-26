<?php

namespace App\Modules\Careers\Listeners;

use App\Modules\Careers\Events\OpportunityPublished;
use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Notifications\MatchingOpportunity;

/**
 * A newly published opportunity is checked against every job alert. Each
 * member hears about it once, however many of their alerts match; never the
 * poster; never a deactivated account.
 */
class SendJobAlerts
{
    public function handle(OpportunityPublished $event): void
    {
        $job = $event->job;

        $matches = JobAlert::query()
            ->with('user')
            ->where('user_id', '!=', $job->user_id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn (JobAlert $alert) => $alert->matches($job));

        foreach ($matches->groupBy('user_id') as $alerts) {
            $alerts->first()->user->notify(new MatchingOpportunity($job, $alerts->first()));
        }

        JobAlert::whereKey($matches->modelKeys())->update(['last_sent_at' => now()]);
    }
}
