<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /** Job posts this user has published. */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    /** Job applications this user has submitted. */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    // ------------------------------------------------------------------
    // Role helpers  (prototype module #2: Role-Based Access)
    // ------------------------------------------------------------------

    /** True when the user holds ANY of the given role slugs. */
    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isExpert(): bool
    {
        return $this->hasRole(Role::EXPERT);
    }

    public function isMember(): bool
    {
        return $this->hasRole(Role::MEMBER);
    }

    /** Admins and Experts may publish jobs / internships. */
    public function canPostJobs(): bool
    {
        return $this->hasRole(Role::ADMIN, Role::EXPERT);
    }

    /** Admins may moderate any community content. */
    public function canModerate(): bool
    {
        return $this->isAdmin();
    }

    // ------------------------------------------------------------------
    // Presentation helpers
    // ------------------------------------------------------------------

    public function roleName(): string
    {
        return $this->role?->name ?? 'Unassigned';
    }

    /** Avatar URL, falling back to an initials placeholder. */
    public function avatarUrl(): ?string
    {
        $path = $this->profile?->avatar_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last) ?: 'U';
    }
}
