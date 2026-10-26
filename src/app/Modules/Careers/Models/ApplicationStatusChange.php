<?php

namespace App\Modules\Careers\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step in an application's history (roadmap 1.14). from_status is null
 * for the submission itself. The message is shown to the applicant.
 */
class ApplicationStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'from_status', 'to_status', 'message'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /** Who made the change: the applicant for the submission, otherwise a reviewer. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
