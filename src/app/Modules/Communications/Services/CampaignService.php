<?php

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Jobs\SendCampaignBatch;
use App\Modules\Communications\Mail\CampaignMail;
use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\CampaignRecipient;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Campaigns: Draft → Scheduled → Sending → Sent (FR7, roadmap 3.16).
 * Starting a campaign lists every confirmed subscriber as a recipient and
 * queues them in batches; each recipient is claimed before its email goes
 * out, so overlapping workers never send anyone the same campaign twice.
 */
class CampaignService
{
    public function create(User $author, array $data): Campaign
    {
        $campaign = new Campaign($data + ['status' => 'draft']);
        $campaign->created_by = $author->id;
        $campaign->save();

        return $campaign;
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);

        return $campaign;
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }

    /** The campaign exactly as subscribers will see it, to the sender's own inbox. */
    public function sendTest(Campaign $campaign, User $to): void
    {
        Mail::to($to->email)->send(new CampaignMail($campaign, null));
    }

    public function schedule(Campaign $campaign, Carbon $at): void
    {
        $campaign->update(['status' => 'scheduled', 'scheduled_at' => $at]);
    }

    public function unschedule(Campaign $campaign): void
    {
        $campaign->update(['status' => 'draft', 'scheduled_at' => null]);
    }

    /** Send now, or when the scheduler finds it due. Only one caller wins the claim. */
    public function start(Campaign $campaign): bool
    {
        $claimed = Campaign::whereKey($campaign->id)->whereIn('status', ['draft', 'scheduled'])
            ->update(['status' => 'sending', 'started_at' => now(), 'updated_at' => now()]);
        if (! $claimed) {
            return false;
        }

        Subscriber::where('status', 'subscribed')->select('id', 'email')->chunkById(500, function ($subscribers) use ($campaign) {
            CampaignRecipient::insertOrIgnore($subscribers->map(fn (Subscriber $s) => [
                'newsletter_campaign_id' => $campaign->id, 'newsletter_subscriber_id' => $s->id, 'email' => $s->email,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ])->all());
        });

        $campaign->refresh();
        $campaign->update(['recipients_count' => $campaign->recipients()->count()]);
        $campaign->recipients()->select('id')->chunkById(Campaign::BATCH, fn ($batch) => SendCampaignBatch::dispatch($campaign->id, $batch->pluck('id')->all()));
        $this->finishIfDone($campaign);

        return true;
    }

    /** Scheduled campaigns whose time has come (every minute, routes/console.php). */
    public function startDue(): int
    {
        return Campaign::where('status', 'scheduled')->where('scheduled_at', '<=', now())->get()
            ->filter(fn (Campaign $c) => $this->start($c))->count();
    }

    /** Called by the queued job for its batch. */
    public function deliver(Campaign $campaign, array $recipientIds): void
    {
        foreach (CampaignRecipient::with('subscriber')->whereIn('id', $recipientIds)->where('status', 'pending')->get() as $recipient) {
            $claimed = CampaignRecipient::whereKey($recipient->id)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()]);
            if (! $claimed) {
                continue;
            }
            if ($recipient->subscriber?->status !== 'subscribed') {   // unsubscribed since the campaign started
                $recipient->update(['status' => 'skipped']);
                continue;
            }

            try {
                Mail::to($recipient->email)->send(new CampaignMail($campaign, route('newsletter.unsubscribe', $recipient->subscriber->token)));
                $recipient->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } catch (Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            }
        }

        $this->finishIfDone($campaign);
    }

    private function finishIfDone(Campaign $campaign): void
    {
        if (! $campaign->recipients()->whereIn('status', ['pending', 'sending'])->exists()) {
            Campaign::whereKey($campaign->id)->where('status', 'sending')->update(['status' => 'sent', 'sent_at' => now(), 'updated_at' => now()]);
        }
    }
}
