<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'body', 'views_count', 'is_closed',
    ];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->latest();
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    // ------------------------------------------------------------------
    // Voting helpers
    // ------------------------------------------------------------------

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

    // ------------------------------------------------------------------
    // Query scopes  (used by the questions index filters)
    // ------------------------------------------------------------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('body', 'like', "%{$term}%");
        });
    }

    public function scopeInCategory(Builder $query, ?string $slug): Builder
    {
        if (blank($slug)) {
            return $query;
        }

        return $query->whereHas('category', fn (Builder $q) => $q->where('slug', $slug));
    }

    public function scopeWithTag(Builder $query, ?string $slug): Builder
    {
        if (blank($slug)) {
            return $query;
        }

        return $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $slug));
    }

    /** Generate a URL-safe, unique slug from a title. */
    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'question';
        $slug = $base;
        $i    = 1;

        while (static::where('slug', $slug)
                     ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                     ->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function excerpt(int $words = 30): string
    {
        return Str::words(strip_tags($this->body), $words);
    }
}
