<?php

namespace App\Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One address a campaign goes to, and what happened to it (roadmap 3.16). */
class CampaignRecipient extends Model
{
    protected $table = 'newsletter_campaign_recipients';

    protected $fillable = ['newsletter_campaign_id', 'newsletter_subscriber_id', 'email', 'status', 'error', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
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
