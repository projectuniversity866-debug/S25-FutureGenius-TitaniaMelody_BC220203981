<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A reply in a project feedback thread. */
class ProjectThreadReply extends Model
{
    protected $fillable = ['user_id', 'body'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ProjectThread::class, 'project_thread_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
