<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved search that notifies its owner when a matching opportunity is
 * published (roadmap 1.15). Empty criteria match everything.
 */
class JobAlert extends Model
{
    /** Most alerts one member may keep. */
    public const MAX_PER_USER = 10;

    protected $fillable = ['keywords', 'type', 'work_mode', 'last_sent_at'];

    protected function casts(): array
    {
        return ['last_sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Same rules as the board's filters (JobPost scopes search / ofType / ofWorkMode). */
    public function matches(JobPost $job): bool
    {
        if ($this->type && $this->type !== $job->type) {
            return false;
        }
        if ($this->work_mode && $this->work_mode !== $job->work_mode) {
            return false;
        }
        if (blank($this->keywords)) {
            return true;
        }

        $haystack = mb_strtolower($job->title.' '.$job->company.' '.$job->location);

        return str_contains($haystack, mb_strtolower(trim($this->keywords)));
    }

    /** "hackathon · remote · “laravel”" — or "Every new opportunity". */
    public function describe(): string
    {
        $parts = array_filter([
            $this->type ? (JobPost::TYPES[$this->type][1] ?? $this->type) : null,
            $this->work_mode ? ucfirst($this->work_mode) : null,
            filled($this->keywords) ? '“'.$this->keywords.'”' : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Every new opportunity';
    }

    /** The board URL that shows what this alert watches. */
    public function boardUrl(): string
    {
        return route('jobs.index', array_filter(['q' => $this->keywords, 'type' => $this->type, 'mode' => $this->work_mode]));
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
