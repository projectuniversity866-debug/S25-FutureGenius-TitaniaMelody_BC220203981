<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A feedback thread in a project, optionally about one file (roadmap 3.4). */
class ProjectThread extends Model
{
    protected $fillable = ['user_id', 'project_file_id', 'title', 'body', 'status', 'resolved_by', 'resolved_at', 'replies_count', 'last_activity_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime', 'last_activity_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** Oldest first, like a conversation. */
    public function replies(): HasMany
    {
        return $this->hasMany(ProjectThreadReply::class)->oldest()->orderBy('id');
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
