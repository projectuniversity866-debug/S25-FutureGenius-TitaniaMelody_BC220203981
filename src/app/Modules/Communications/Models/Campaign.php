<?php

namespace App\Modules\Communications\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A newsletter email to every confirmed subscriber (FR7, roadmap 3.16). Times are stored in UTC. */
class Campaign extends Model
{
    protected $table = 'newsletter_campaigns';

    /** status => [label, icon] */
    public const STATUSES = [
        'draft'     => ['Draft', 'pencil-square'],
        'scheduled' => ['Scheduled', 'clock'],
        'sending'   => ['Sending', 'send'],
        'sent'      => ['Sent', 'check2-circle'],
    ];

    /** Time zones offered for scheduling. */
    public const TIMEZONES = [
        'Asia/Karachi' => 'Pakistan (PKT, UTC+5)',
        'Asia/Dubai'   => 'Gulf (GST, UTC+4)',
        'Europe/London' => 'London (UK time)',
        'America/New_York' => 'New York (US Eastern)',
        'UTC'          => 'UTC',
    ];

    /** Recipients per queued job. */
    public const BATCH = 50;

    protected $fillable = ['subject', 'body', 'status', 'timezone', 'scheduled_at', 'started_at', 'sent_at', 'recipients_count'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'started_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'newsletter_campaign_id');
    }

    /** e.g. "Sat 7 Nov 2026, 10:00 PKT" in the campaign's own zone. */
    public function scheduleLabel(): ?string
    {
        return $this->scheduled_at?->copy()->setTimezone($this->timezone)->format('D j M Y, H:i T');
    }
}
