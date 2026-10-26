<?php

namespace App\Modules\Identity\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Identity is the core module and depends on no other module. Feature
 * modules attach their own relations to the user when they boot:
 *
 * @property-read \Illuminate\Database\Eloquent\Collection $questions        Community
 * @property-read \Illuminate\Database\Eloquent\Collection $answers          Community
 * @property-read \Illuminate\Database\Eloquent\Collection $votes            Community
 * @property-read \Illuminate\Database\Eloquent\Collection $bookmarks        Community
 * @property-read \Illuminate\Database\Eloquent\Collection $followedTags     Community
 * @property-read \Illuminate\Database\Eloquent\Collection $jobPosts         Careers
 * @property-read \Illuminate\Database\Eloquent\Collection $jobApplications  Careers
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

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

    /**
     * Account settings and their defaults. Notification categories match
     * AppNotification::category(); in-app notifications are always on.
     */
    public const DEFAULT_SETTINGS = [
        'profile_visibility' => 'public',   // public | members
        'email_answers'      => true,        // answers to my questions, accepted answers
        'email_applications' => true,        // applicants, application status changes
        'email_follows'      => false,       // new questions in tags I follow (opt-in: can be many)
        'email_alerts'       => true,        // opportunities matching my job alerts (I asked for them)
        'email_projects'     => true,        // feedback and tasks assigned in my projects
        'email_events'       => true,        // my event registrations and waitlist moves
        'email_consulting'   => true,        // consulting sessions booked or cancelled
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'settings'          => 'array',
        ];
    }

    public function setting(string $key): mixed
    {
        return ($this->settings ?? [])[$key] ?? self::DEFAULT_SETTINGS[$key] ?? null;
    }

    public function wantsEmailFor(string $category): bool
    {
        return (bool) $this->setting('email_'.$category);
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

    /** Google / GitHub identities linked to this account. */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
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

    /**
     * True when the user's role grants the permission (PermissionCatalog).
     * Every slug is also a Gate ability, so `$user->can('users.manage')`,
     * `@can('users.manage')` and the `can:users.manage` middleware all work.
     */
    public function hasPermission(string $slug): bool
    {
        if (! $this->role) {
            return false;
        }

        return $this->role->loadMissing('permissions')->permissions->contains('slug', $slug);
    }

    public function canPostJobs(): bool
    {
        return $this->hasPermission('opportunities.publish');
    }

    public function canModerate(): bool
    {
        return $this->hasPermission('content.moderate');
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
