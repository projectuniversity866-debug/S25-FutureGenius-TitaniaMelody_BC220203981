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

    /**
     * Opportunity types (FR5 + the "professional opportunities" vision item,
     * roadmap 1.13): label, plural, board section, icon, badge tone, and what
     * the money fields mean for that type (null = no money fields).
     */
    public const TYPES = [
        'job'             => ['Job', 'Jobs', 'Careers', 'briefcase', 'primary', 'Salary'],
        'internship'      => ['Internship', 'Internships', 'Careers', 'mortarboard', 'warning', 'Stipend'],
        'scholarship'     => ['Scholarship', 'Scholarships', 'Funding & programmes', 'award', 'success', 'Award'],
        'fellowship'      => ['Fellowship', 'Fellowships', 'Funding & programmes', 'person-badge', 'info', 'Stipend'],
        'grant'           => ['Grant', 'Grants', 'Funding & programmes', 'cash-stack', 'dark', 'Funding'],
        'hackathon'       => ['Hackathon', 'Hackathons', 'Events & research', 'lightning-charge', 'danger', 'Prize pool'],
        'call_for_papers' => ['Call for papers', 'Calls for papers', 'Events & research', 'journal-text', 'light', null],
    ];

    protected $fillable = [
        'user_id', 'organization_id', 'title', 'slug', 'company', 'type', 'work_mode', 'location',
        'description', 'requirements', 'eligibility', 'salary_min', 'salary_max', 'deadline',
        'starts_on', 'ends_on', 'external_url', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'deadline'   => 'date',
            'starts_on'  => 'date',
            'ends_on'    => 'date',
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

    /** The organization profile it was published under, if any. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // ------------------------------------------------------------------
    // Type
    // ------------------------------------------------------------------

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst((string) $this->type);
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][3] ?? 'briefcase';
    }

    /** Bootstrap badge class; the label is always shown too, so colour is never the only cue. */
    public function typeBadge(): string
    {
        return 'text-bg-'.(self::TYPES[$this->type][4] ?? 'primary');
    }

    /** "Salary", "Award", "Prize pool"… or null when the type has no money fields. */
    public function moneyLabel(): ?string
    {
        return array_key_exists($this->type, self::TYPES) ? self::TYPES[$this->type][5] : 'Salary';
    }

    /** "12 – 14 Nov 2026", or null without dates. */
    public function dateRange(): ?string
    {
        if (! $this->starts_on) {
            return null;
        }
        if (! $this->ends_on || $this->ends_on->isSameDay($this->starts_on)) {
            return $this->starts_on->format('d M Y');
        }

        return $this->starts_on->format($this->starts_on->year === $this->ends_on->year ? 'd M' : 'd M Y')
            .' – '.$this->ends_on->format('d M Y');
    }

    /** Type options grouped by board section, for the form's <optgroup>s. */
    public static function typeGroups(): array
    {
        $groups = [];
        foreach (self::TYPES as $key => $type) {
            $groups[$type[2]][$key] = $type[0];
        }

        return $groups;
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
        if ($this->moneyLabel() === null) {
            return 'Not applicable';
        }

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
        return is_string($type) && array_key_exists($type, self::TYPES)
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

    /** Published and still before its deadline — the same rule as isOpen(). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
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
