<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** An employer, university or other body that publishes opportunities (roadmap 1.12). */
class Organization extends Model
{
    /** Headcount bands shown on the profile. */
    public const SIZES = ['1-10', '11-50', '51-200', '201-1000', '1000+'];

    protected $fillable = [
        'user_id', 'name', 'slug', 'tagline', 'about', 'website', 'location', 'industry', 'size',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** The member who manages this profile. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('industry', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%");
        });
    }

    /** Website shown without "https://" and a trailing slash. */
    public function websiteHost(): ?string
    {
        return $this->website ? rtrim(preg_replace('#^https?://(www\.)?#i', '', $this->website), '/') : null;
    }

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'organization';
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
