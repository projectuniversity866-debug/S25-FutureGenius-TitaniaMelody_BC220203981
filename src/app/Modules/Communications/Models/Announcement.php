<?php

namespace App\Modules\Communications\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** A site announcement shown as a banner between its start and end (FR7 "Community updates", roadmap 3.18). */
class Announcement extends Model
{
    /** tone => [label, icon, Bootstrap alert class] — the icon and word carry the meaning, not the colour. */
    public const TONES = [
        'info'    => ['News', 'megaphone', 'alert-info'],
        'success' => ['Good news', 'stars', 'alert-success'],
        'warning' => ['Important', 'exclamation-triangle', 'alert-warning'],
    ];

    /** audience => label */
    public const AUDIENCES = [
        'everyone' => 'Everyone',
        'members'  => 'Signed-in members only',
        'guests'   => 'Visitors who are not signed in',
    ];

    protected $fillable = ['title', 'body', 'link_url', 'link_label', 'tone', 'audience', 'is_dismissible', 'timezone', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['is_dismissible' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    /** Started and not yet ended. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Who should see it: signed-in members, visitors, or both. */
    public function scopeFor(Builder $query, ?User $user): Builder
    {
        return $query->whereIn('audience', ['everyone', $user ? 'members' : 'guests']);
    }

    /** upcoming | live | ended */
    public function phase(): string
    {
        return $this->starts_at->isFuture() ? 'upcoming' : ($this->ends_at && ! $this->ends_at->isFuture() ? 'ended' : 'live');
    }

    /** e.g. "Mon 2 Nov 2026, 09:00 PKT" in the announcement's own zone. */
    public function timeLabel(?Carbon $time): ?string
    {
        return $time ? $time->copy()->setTimezone($this->timezone)->format('D j M Y, H:i T') : null;
    }
}
