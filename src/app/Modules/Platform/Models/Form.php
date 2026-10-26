<?php

namespace App\Modules\Platform\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A form built by a creator and filled in by respondents (roadmap 2.4).
 * Status: draft (only its creator can preview it) → open → closed.
 */
class Form extends Model
{
    public const STATUSES = ['draft', 'open', 'closed'];

    protected $fillable = [
        'user_id', 'title', 'slug', 'description', 'status', 'requires_login', 'one_response_per_user',
        'confirmation_message', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_login'        => 'boolean',
            'one_response_per_user' => 'boolean',
            'closes_at'             => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    /** Open, and not past its closing time. */
    public function isAcceptingResponses(): bool
    {
        return $this->status === 'open' && ($this->closes_at === null || $this->closes_at->isFuture());
    }

    /** "draft" / "open" / "closed", counting a passed closing time as closed. */
    public function displayStatus(): string
    {
        return $this->status === 'open' && ! $this->isAcceptingResponses() ? 'closed' : $this->status;
    }

    public static function makeSlug(string $title): string
    {
        // Public link: readable start plus a random part, so forms cannot be guessed.
        return Str::limit(Str::slug($title) ?: 'form', 60, '').'-'.Str::lower(Str::random(6));
    }
}
