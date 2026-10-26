<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'reviewed', 'shortlisted', 'rejected', 'accepted'];

    /** The forward path shown as a progress bar; "rejected" can end it at any step. */
    public const STAGES = ['pending', 'reviewed', 'shortlisted', 'accepted'];

    /** Applicant-facing wording for each status. */
    public const STATUS_TEXT = [
        'pending'     => 'Submitted — waiting for review',
        'reviewed'    => 'Reviewed by the organizer',
        'shortlisted' => 'Shortlisted for the next round',
        'rejected'    => 'Not taken forward',
        'accepted'    => 'Accepted',
    ];

    /** Status history, oldest first (roadmap 1.14). */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(ApplicationStatusChange::class)->oldest('created_at')->oldest('id');
    }

    /** Reviewers' private notes, newest first. */
    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest()->latest('id');
    }

    protected $fillable = [
        'job_post_id', 'user_id', 'cover_letter', 'resume_path', 'status',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
