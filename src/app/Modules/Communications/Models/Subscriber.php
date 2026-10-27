<?php

namespace App\Modules\Communications\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Someone on the newsletter list (FR7, roadmap 3.15). */
class Subscriber extends Model
{
    protected $table = 'newsletter_subscribers';

    /** A confirmation link works this long after it was sent. */
    public const CONFIRM_DAYS = 7;

    protected $fillable = ['email', 'user_id', 'status', 'token', 'confirmed_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newToken(): string
    {
        return Str::random(64);
    }

    public function confirmationExpired(): bool
    {
        return $this->status === 'pending' && $this->updated_at->addDays(self::CONFIRM_DAYS)->isPast();
    }
}
