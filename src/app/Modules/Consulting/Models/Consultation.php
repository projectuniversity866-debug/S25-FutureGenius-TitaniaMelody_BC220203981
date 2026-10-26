<?php

namespace App\Modules\Consulting\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** A booked consulting session (FR6, roadmap 3.12). Times are stored in UTC. */
class Consultation extends Model
{
    /** status => [label, icon] */
    public const STATUSES = [
        'confirmed' => ['Confirmed', 'calendar-check'],
        'completed' => ['Completed', 'check2-circle'],
        'cancelled' => ['Cancelled', 'calendar-x'],
    ];

    /** A client may hold this many upcoming sessions at once (across consultants). */
    public const MAX_UPCOMING_PER_CLIENT = 5;

    /** The joining link opens this long before the start. */
    public const JOIN_OPENS_MINUTES = 10;

    protected $fillable = [
        'client_id', 'consulting_client_id', 'starts_at', 'ends_at', 'topic', 'message', 'meeting_url', 'status', 'slot_key',
        'cancelled_by', 'cancel_reason', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'consultant_profile_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function clientRecord(): BelongsTo
    {
        return $this->belongsTo(ConsultingClient::class, 'consulting_client_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Still taking up time in the calendar. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /** Overlaps [from, to): starts before `to` and ends after `from`. */
    public function scopeOverlapping(Builder $query, $from, $to): Builder
    {
        return $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'confirmed' && $this->starts_at->isFuture();
    }

    public function isJoinable(): bool
    {
        return $this->status === 'confirmed' && now()->between($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_MINUTES), $this->ends_at);
    }

    /** "Mon 2 Nov 2026, 10:00 – 10:45 PKT", in the consultant's zone. */
    public function whenLabel(): string
    {
        $tz = $this->profile->timezone;
        $start = $this->starts_at->copy()->setTimezone($tz);

        return $start->format('D j M Y, H:i').' – '.$this->ends_at->copy()->setTimezone($tz)->format('H:i').' '.$start->format('T');
    }

    public static function slotKey(int $profileId, $start): string
    {
        return $profileId.':'.$start->copy()->utc()->format('YmdHi');
    }

    public static function jitsiRoom(): string
    {
        return 'https://meet.jit.si/AQDesk-Session-'.Str::random(16);
    }
}
