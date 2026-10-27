<?php

namespace App\Modules\Communications;

use App\Modules\Communications\Console\SendDueCampaigns;
use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Policies\CampaignPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR7 Newsletter and Announcements — subscriptions with double opt-in
 * (roadmap 3.15) and campaigns with scheduled, queued sending (3.16);
 * delivery reports (3.17) and announcements (3.18) follow.
 */
class CommunicationsServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Communications';

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendDueCampaigns::class]);
        }
    }

    protected function policies(): array
    {
        return [Campaign::class => CampaignPolicy::class];
    }

    /** A deleted account's address leaves the newsletter list too. */
    protected function personalDataErasers(): array
    {
        return [fn (User $user) => Subscriber::where('user_id', $user->id)->orWhere('email', mb_strtolower($user->email))->delete()];
    }
}
