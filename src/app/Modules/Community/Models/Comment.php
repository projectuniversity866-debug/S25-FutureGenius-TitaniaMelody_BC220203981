<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Services\CommentService;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Contracts\Reportable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Comment extends Model implements Reportable
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

    // Reporting (Governance, roadmap 2.5)

    public function reportLabel(): string
    {
        return 'Comment on “'.Str::limit($this->question()->title, 70).'”';
    }

    public function reportUrl(): string
    {
        return route('questions.show', $this->question()).'#comment-'.$this->id;
    }

    public function reportExcerpt(): string
    {
        return $this->body;
    }

    public function reportAuthor(): ?User
    {
        return $this->user;
    }

    public function removeAsModerator(User $moderator): void
    {
        app(CommentService::class)->delete($this);
    }
}
