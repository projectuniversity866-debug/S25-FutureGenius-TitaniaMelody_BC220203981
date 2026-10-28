<?php

namespace App\Modules\Communications\Jobs;

use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Sends one batch of a campaign (roadmap 3.16); see CampaignService::deliver(). */
class SendCampaignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @param  array<int, int>  $recipientIds */
    public function __construct(public int $campaignId, public array $recipientIds) {}

    public function handle(CampaignService $campaigns): void
    {
        if ($campaign = Campaign::find($this->campaignId)) {
            $campaigns->deliver($campaign, $this->recipientIds);
        }
    }
}
