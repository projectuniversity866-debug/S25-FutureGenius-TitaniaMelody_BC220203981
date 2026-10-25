<?php

namespace App\Modules\Community\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One line in a member's reputation ledger. */
class ReputationEvent extends Model
{
    public const UPDATED_AT = null;

    public const LABELS = [
        'question_upvoted' => 'Question upvoted',
        'answer_upvoted'   => 'Answer upvoted',
        'answer_accepted'  => 'Answer accepted',
        'downvoted'        => 'Post downvoted',
        'adjustment'       => 'Adjustment',
    ];

    protected $fillable = ['user_id', 'actor_id', 'type', 'points', 'subject_type', 'subject_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
