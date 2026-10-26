<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Community\Models\Category;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\MarkdownRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An article or step-by-step guide in the Knowledge Hub (roadmap 2.2).
 * A null published_at means it is still a draft, visible only to its author.
 */
class Article extends Model
{
    public const KINDS = ['article' => 'Article', 'guide' => 'Guide'];

    /** Average adult reading speed, for "5 min read". */
    public const WORDS_PER_MINUTE = 200;

    protected $fillable = [
        'user_id', 'category_id', 'kind', 'title', 'slug', 'summary', 'body', 'reading_minutes', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The four topic tracks are shared with Community Q&A. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? 'Article';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('title', 'like', "%{$term}%")->orWhere('summary', 'like', "%{$term}%"));
    }

    public static function readingMinutes(string $markdown): int
    {
        $words = str_word_count(app(MarkdownRenderer::class)->plain($markdown));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i    = 1;

        while (static::where('slug', $slug)
                     ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                     ->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
