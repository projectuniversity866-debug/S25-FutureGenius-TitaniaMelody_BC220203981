<?php

namespace App\Modules\Community\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'user_id', 'body', 'is_accepted'];

    protected function casts(): array
    {
        return ['is_accepted' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function score(): int
    {
        // Use the eager-loaded relation when the caller has already loaded it,
        // otherwise fall back to an aggregate query. Prevents N+1 on list pages.
        if ($this->relationLoaded('votes')) {
            return (int) $this->votes->sum('value');
        }

        return (int) $this->votes()->sum('value');
    }

    public function voteOf(?User $user): ?Vote
    {
        if (! $user) {
            return null;
        }

        if ($this->relationLoaded('votes')) {
            return $this->votes->firstWhere('user_id', $user->id);
        }

        return $this->votes()->where('user_id', $user->id)->first();
    }
}
