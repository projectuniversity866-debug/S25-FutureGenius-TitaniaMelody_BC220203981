<?php

namespace App\Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One address a campaign goes to, and what happened to it (roadmap 3.16): delivered, failed, opened (3.17). */
class CampaignRecipient extends Model
{
    protected $table = 'newsletter_campaign_recipients';

    /** status => [label, icon] */
    public const STATUSES = [
        'sent'    => ['Sent', 'check2-circle'],   // accepted by the mail server
        'failed'  => ['Failed', 'exclamation-triangle'],
        'skipped' => ['Skipped (unsubscribed meanwhile)', 'skip-forward'],
        'pending' => ['Waiting in the queue', 'hourglass-split'],
        'sending' => ['Sending', 'send'],
    ];

    protected $fillable = ['newsletter_campaign_id', 'newsletter_subscriber_id', 'email', 'status', 'error', 'sent_at', 'token', 'opened_at', 'open_count'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'opened_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'newsletter_campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'newsletter_subscriber_id');
    }
}
