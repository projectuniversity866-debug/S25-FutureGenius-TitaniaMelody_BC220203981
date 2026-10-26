<?php

namespace App\Modules\Consulting\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A consultant's public profile and booking settings (FR6, roadmap 3.11). */
class ConsultantProfile extends Model
{
    public const SESSION_LENGTHS = [30, 45, 60, 90];

    /** Same zones events offer (EventsMedia keeps its own copy; modules do not depend on each other). */
    public const TIMEZONES = [
        'Asia/Karachi'     => 'Pakistan (PKT, UTC+5)',
        'Asia/Dubai'       => 'Gulf (GST, UTC+4)',
        'Europe/London'    => 'London (UK time)',
        'America/New_York' => 'New York (US Eastern)',
        'UTC'              => 'UTC',
    ];

    public const WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $fillable = [
        'user_id', 'slug', 'headline', 'bio', 'expertise', 'session_minutes', 'price_pkr', 'timezone', 'notice_hours', 'window_days', 'is_accepting',
    ];

    protected function casts(): array
    {
        return ['expertise' => 'array', 'is_accepting' => 'boolean', 'session_minutes' => 'integer', 'price_pkr' => 'integer',
            'notice_hours' => 'integer', 'window_days' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Weekly hours, Monday first, earliest first. */
    public function rules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class)->orderBy('weekday')->orderBy('starts');
    }

    public function daysOff(): HasMany
    {
        return $this->hasMany(AvailabilityException::class)->orderBy('date');
    }

    /** "Free" or "PKR 2,500 per session". */
    public function priceLabel(): string
    {
        return $this->price_pkr ? 'PKR '.number_format($this->price_pkr).' per session' : 'Free';
    }

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'consultant', 60, '');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
