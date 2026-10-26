<?php

namespace App\Modules\EventsMedia\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\FormResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A member's place at an event: a seat, a waitlist spot, or a cancelled one. */
class EventRegistration extends Model
{
    /** status => [label, icon] */
    public const STATUSES = [
        'confirmed'  => ['Confirmed', 'check-circle'],
        'waitlisted' => ['On the waitlist', 'hourglass-split'],
        'cancelled'  => ['Cancelled', 'x-circle'],
    ];

    protected $fillable = ['user_id', 'form_response_id', 'status', 'queued_at', 'promoted_at', 'cancelled_at', 'reminded_day_at', 'reminded_hour_at'];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime', 'promoted_at' => 'datetime', 'cancelled_at' => 'datetime',
            'reminded_day_at' => 'datetime', 'reminded_hour_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Answers to the event's registration questions (roadmap 3.7). */
    public function response(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'form_response_id');
    }

    public function isActive(): bool
    {
        return $this->status !== 'cancelled';
    }

    /** 1-based place on the waitlist, or null when not waitlisted. */
    public function waitlistPosition(): ?int
    {
        if ($this->status !== 'waitlisted') {
            return null;
        }

        return static::where('event_id', $this->event_id)->where('status', 'waitlisted')
            ->where(fn ($q) => $q->where('queued_at', '<', $this->queued_at)
                ->orWhere(fn ($q) => $q->where('queued_at', $this->queued_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }
}
