<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'reviewed', 'shortlisted', 'rejected', 'accepted'];

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

    /** Bootstrap badge colour for each application status. */
    public function statusColor(): string
    {
        return match ($this->status) {
            'accepted'    => 'success',
            'shortlisted' => 'info',
            'reviewed'    => 'primary',
            'rejected'    => 'danger',
            default       => 'secondary',
        };
    }
}
