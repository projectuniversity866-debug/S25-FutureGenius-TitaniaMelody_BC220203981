<?php

namespace App\Modules\Community\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    public const MAX_LENGTH = 600;

    protected $fillable = ['user_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The question or answer this comment is on. */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The question page this comment appears on. */
    public function question(): Question
    {
        return $this->commentable instanceof Answer ? $this->commentable->question : $this->commentable;
    }
}
