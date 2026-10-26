<?php

namespace App\Modules\Governance\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A member's report that some content breaks the community guidelines (roadmap 2.5). */
class Report extends Model
{
    /** reason => label shown to reporters and moderators */
    public const REASONS = [
        'spam'           => 'Spam or advertising',
        'abuse'          => 'Harassment, hate or abuse',
        'misinformation' => 'Misleading or false information',
        'off_topic'      => 'Off-topic or low quality',
        'privacy'        => 'Shares someone’s personal information',
        'other'          => 'Something else',
    ];

    /** Morph aliases that can be reported (each model implements Platform\Contracts\Reportable). */
    public const REPORTABLE = ['question', 'answer', 'comment', 'article', 'job_post'];

    public const STATUSES = ['open', 'actioned', 'dismissed'];

    protected $fillable = ['reason', 'details'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /** Null once the content has been removed. */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
