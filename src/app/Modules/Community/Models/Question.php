<?php

namespace App\Modules\Community\Models;

use App\Modules\Community\Services\QuestionService;
use App\Modules\Platform\Contracts\Reportable;
use App\Modules\Platform\Services\MarkdownRenderer;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Question extends Model implements Reportable
{
    use HasFactory;

    // ------------------------------------------------------------------
    // Reporting (Governance, roadmap 2.5)
    // ------------------------------------------------------------------

    public function reportLabel(): string
    {
        return 'Question “'.Str::limit($this->title, 80).'”';
    }

    public function reportUrl(): string
    {
        return route('questions.show', $this);
    }

    public function reportExcerpt(): string
    {
        return $this->excerpt(40);
    }

    public function reportAuthor(): ?User
    {
        return $this->user;
    }

    public function removeAsModerator(User $moderator): void
    {
        app(QuestionService::class)->delete($this);
    }

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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->oldest();
    }

    /** Earlier versions, newest first. */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisable')->latest('id');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * Lists load this as an "is_saved" column (withExists) to avoid a query
     * per row; otherwise it is looked up.
     */
    public function isSavedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (array_key_exists('is_saved', $this->attributes)) {
            return (bool) $this->attributes['is_saved'];
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
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

    /** Questions carrying at least one tag the user follows. */
    public function scopeInTagsFollowedBy(Builder $query, User $user): Builder
    {
        $tagIds = $user->followedTags()->pluck('tags.id');

        return $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $tagIds));
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
        // Plain text: Markdown symbols (**, `, #) must not show in lists.
        return Str::words(app(MarkdownRenderer::class)->plain($this->body), $words);
    }
}
