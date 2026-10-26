<?php

namespace App\Modules\EventsMedia\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An educational event (FR4, roadmap 3.6). Seats are first come, first
 * served; when it is full, new registrations join a waitlist and move up
 * as seats free.  Status: draft → published → (cancelled).
 */
class Event extends Model
{
    /** format => [label, icon] */
    public const FORMATS = [
        'in_person' => ['In person', 'geo-alt'],
        'online'    => ['Online', 'camera-video'],
        'hybrid'    => ['Hybrid', 'broadcast'],
    ];

    /** Time zones offered to organizers; times are stored in UTC and shown in the event's own zone. */
    public const TIMEZONES = [
        'Asia/Karachi' => 'Pakistan (PKT, UTC+5)',
        'Asia/Dubai'   => 'Gulf (GST, UTC+4)',
        'Europe/London' => 'London (UK time)',
        'America/New_York' => 'New York (US Eastern)',
        'UTC'          => 'UTC',
    ];

    protected $fillable = [
        'user_id', 'form_id', 'title', 'slug', 'summary', 'description', 'format', 'venue', 'meeting_url', 'meeting_notes',
        'starts_at', 'ends_at', 'timezone', 'capacity', 'registration_closes_at', 'status', 'cancel_reason', 'organizer_reminded_at',
    ];

    /** host => provider label, for the joining link (roadmap 3.8). Any other https link is "Online meeting". */
    public const MEETING_PROVIDERS = [
        'zoom.us'         => 'Zoom',
        'meet.google.com' => 'Google Meet',
        'meet.jit.si'     => 'Jitsi Meet',
        'teams.microsoft.com' => 'Microsoft Teams',
    ];

    /** Joining details open this long before the start (and the "Join now" button lights up). */
    public const JOIN_OPENS_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_closes_at' => 'datetime',
            'organizer_reminded_at' => 'datetime', 'capacity' => 'integer',
        ];
    }

    public function isOnline(): bool
    {
        return $this->format !== 'in_person';
    }

    /** "Zoom", "Google Meet", "Jitsi Meet"… from the link's host (sub-domains such as us02web.zoom.us count). */
    public function meetingProvider(): ?string
    {
        $host = strtolower((string) parse_url((string) $this->meeting_url, PHP_URL_HOST));
        if ($host === '') {
            return null;
        }
        foreach (self::MEETING_PROVIDERS as $domain => $label) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $label;
            }
        }

        return 'Online meeting';
    }

    /** From a few minutes before the start until the end. */
    public function isJoinable(): bool
    {
        return $this->status === 'published' && now()->between($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_MINUTES), $this->ends_at);
    }

    /** A fresh, hard-to-guess Jitsi room — no account or API key needed. */
    public static function jitsiRoom(string $title): string
    {
        return 'https://meet.jit.si/AQDesk-'.Str::limit(Str::studly(Str::slug($title)), 40, '').'-'.Str::random(10);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Registration questions built with the Form Builder (roadmap 3.7). */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Not finished yet (includes events happening right now). */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>=', now());
    }

    public function confirmedCount(): int
    {
        return $this->registrations()->where('status', 'confirmed')->count();
    }

    public function waitlistCount(): int
    {
        return $this->registrations()->where('status', 'waitlisted')->count();
    }

    /** Free seats, or null when there is no limit. */
    public function seatsLeft(?int $confirmed = null): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - ($confirmed ?? $this->confirmedCount()));
    }

    public function isFull(?int $confirmed = null): bool
    {
        return $this->seatsLeft($confirmed) === 0;
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /** Published, not started yet, and before any registration deadline. */
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'published' && $this->starts_at->isFuture()
            && ($this->registration_closes_at === null || $this->registration_closes_at->isFuture());
    }

    /** Has registration questions to answer. */
    public function hasQuestions(): bool
    {
        return $this->form !== null && $this->form->fields->isNotEmpty();
    }

    /** A UTC time in the event's own zone, for display. */
    public function local(CarbonInterface $time): CarbonInterface
    {
        return $time->copy()->setTimezone($this->timezone);
    }

    /** "Sat 14 Nov 2026, 10:00 – 12:30 PKT" (or across days). */
    public function whenLabel(): string
    {
        $start = $this->local($this->starts_at);
        $end = $this->local($this->ends_at);

        return $start->format('D j M Y, H:i').' – '.($start->isSameDay($end) ? $end->format('H:i') : $end->format('D j M Y, H:i'))
            .' '.$start->format('T');
    }

    /** draft / published / cancelled / ended, for badges. */
    public function displayStatus(): string
    {
        return $this->status === 'published' && $this->hasEnded() ? 'ended' : $this->status;
    }

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title) ?: 'event', 80, '');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
