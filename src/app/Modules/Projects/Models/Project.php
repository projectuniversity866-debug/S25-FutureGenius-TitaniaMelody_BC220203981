<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A shared project space (FR3, roadmap 3.1). */
class Project extends Model
{
    /** role => [label, what it may do] */
    public const ROLES = [
        'owner'        => ['Owner', 'Manages the project, its settings and its members'],
        'collaborator' => ['Collaborator', 'Works on the project: files, feedback and tasks'],
        'viewer'       => ['Viewer', 'Can see the project but not change it'],
    ];

    public const VISIBILITY = [
        'private' => ['Private', 'Only its members can see it'],
        'members' => ['Visible to members', 'Any signed-in member can see it and ask to join'],
    ];

    protected $fillable = ['user_id', 'name', 'slug', 'description', 'visibility', 'status'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Members with their role on the pivot. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withPivot('role')->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    /** Shared files, most recently changed first (roadmap 3.3). */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->latest('updated_at');
    }

    /** The user's role here, or null when they are not a member. */
    public function roleOf(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $member = $this->relationLoaded('members')
            ? $this->members->firstWhere('id', $user->id)
            : $this->members()->whereKey($user->id)->first();

        return $member?->pivot->role;
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i    = 1;

        while (static::where('slug', $slug)
                     ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                     ->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
