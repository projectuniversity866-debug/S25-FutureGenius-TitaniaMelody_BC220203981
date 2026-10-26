<?php

namespace App\Modules\EventsMedia\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A podcast series and its host (FR4, roadmap 3.10). */
class PodcastSeries extends Model
{
    protected $table = 'podcast_series';

    protected $fillable = ['user_id', 'title', 'slug', 'summary', 'description'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Newest first. */
    public function episodes(): HasMany
    {
        return $this->hasMany(PodcastEpisode::class)->orderByDesc('number');
    }

    public function publishedEpisodes(): HasMany
    {
        return $this->episodes()->where('status', 'published');
    }

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title) ?: 'podcast', 80, '');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
