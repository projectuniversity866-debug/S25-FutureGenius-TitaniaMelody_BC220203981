<?php

namespace App\Modules\Communications\Policies;

use App\Modules\Communications\Models\Campaign;
use App\Modules\Identity\Models\User;

/** Senders (newsletter.send) change a campaign only while it is a draft (roadmap 3.16). */
class CampaignPolicy
{
    /** Edit, delete, schedule. */
    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('newsletter.send') && $campaign->status === 'draft';
    }

    public function unschedule(User $user, Campaign $campaign): bool
    {
        return $user->can('newsletter.send') && $campaign->status === 'scheduled';
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return $user->can('newsletter.send') && in_array($campaign->status, ['draft', 'scheduled'], true);
    }
}
