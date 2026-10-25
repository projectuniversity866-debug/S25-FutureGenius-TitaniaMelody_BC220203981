<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'slug', 'company', 'type', 'work_mode', 'location',
        'description', 'requirements', 'salary_min', 'salary_max', 'deadline', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deadline'   => 'date',
            'is_active'  => 'boolean',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** The employer / expert who published this opportunity. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function hasApplicationFrom(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->applications()->where('user_id', $user->id)->exists();
    }

    public function isExpired(): bool
    {
        return $this->deadline !== null && $this->deadline->isPast();
    }

    public function isOpen(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function salaryRange(): string
    {
        if (! $this->salary_min && ! $this->salary_max) {
            return 'Not disclosed';
        }

        if ($this->salary_min && $this->salary_max) {
            return 'PKR '.number_format((float) $this->salary_min).' – '.number_format((float) $this->salary_max);
        }

        return 'PKR '.number_format((float) ($this->salary_min ?: $this->salary_max));
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('company', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%");
        });
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return in_array($type, ['job', 'internship'], true)
            ? $query->where('type', $type)
            : $query;
    }

    public function scopeOfWorkMode(Builder $query, ?string $mode): Builder
    {
        return in_array($mode, ['onsite', 'remote', 'hybrid'], true)
            ? $query->where('work_mode', $mode)
            : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'opportunity';
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
