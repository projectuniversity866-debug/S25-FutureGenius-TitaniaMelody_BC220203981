<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Community\Models\Category;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An ordered route through library resources, e.g. "Start with web
 * accessibility" (roadmap 2.3). Members tick off steps to track progress.
 */
class LearningPath extends Model
{
    protected $fillable = ['user_id', 'category_id', 'title', 'slug', 'summary', 'level', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function curator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LearningPathStep::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    /** Total estimated minutes of its steps (resources without an estimate count as 0). */
    public function totalMinutes(): int
    {
        return (int) $this->steps->sum(fn (LearningPathStep $step) => $step->resource->minutes ?? 0);
    }

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'learning-path';
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
