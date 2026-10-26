<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invitation to join a project (roadmap 3.2). Only a hash of the token is
 * stored; the token itself exists only in the emailed link.
 */
class ProjectInvitation extends Model
{
    public const VALID_DAYS = 7;

    protected $fillable = ['email', 'role', 'token_hash', 'status', 'expires_at', 'responded_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'responded_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Still waiting for an answer and not expired. */
    public function isOpen(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
