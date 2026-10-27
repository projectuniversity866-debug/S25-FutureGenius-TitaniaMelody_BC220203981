<?php

namespace App\Modules\Communications\Console;

use App\Modules\Communications\Services\CampaignService;
use Illuminate\Console\Command;

/** Every minute (routes/console.php): start scheduled campaigns whose time has come (roadmap 3.16). */
class SendDueCampaigns extends Command
{
    protected $signature = 'newsletter:send-due';

    protected $description = 'Start sending scheduled newsletter campaigns that are due';

    public function handle(CampaignService $campaigns): int
    {
        $started = $campaigns->startDue();

        $this->info("Started {$started} ".($started === 1 ? 'campaign' : 'campaigns').'.');

        return self::SUCCESS;
    }
}
