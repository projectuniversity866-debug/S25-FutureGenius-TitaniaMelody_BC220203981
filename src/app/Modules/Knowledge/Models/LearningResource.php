<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Community\Models\Category;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One item in the Learning Hub library: a video, course, tutorial… (roadmap 2.3). */
class LearningResource extends Model
{
    /** kind => [label, icon] */
    public const KINDS = [
        'video'    => ['Video', 'play-btn'],
        'course'   => ['Course', 'mortarboard'],
        'tutorial' => ['Tutorial', 'list-check'],
        'article'  => ['Article', 'file-text'],
        'book'     => ['Book', 'book'],
        'tool'     => ['Tool', 'tools'],
    ];

    public const LEVELS = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

    protected $fillable = [
        'user_id', 'category_id', 'title', 'kind', 'level', 'url', 'provider', 'description', 'minutes', 'is_free',
    ];

    protected function casts(): array
    {
        return ['is_free' => 'boolean'];
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
        return $this->hasMany(LearningPathStep::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind][0] ?? ucfirst($this->kind);
    }

    public function kindIcon(): string
    {
        return self::KINDS[$this->kind][1] ?? 'link-45deg';
    }

    /** "45 min", "3 h 30 min", or null. */
    public function duration(): ?string
    {
        if (! $this->minutes) {
            return null;
        }

        $h = intdiv($this->minutes, 60);
        $m = $this->minutes % 60;

        return trim(($h ? $h.' h ' : '').($m ? $m.' min' : ''));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('title', 'like', "%{$term}%")
            ->orWhere('provider', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
    }
}
