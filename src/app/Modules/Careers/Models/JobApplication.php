<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
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
}
