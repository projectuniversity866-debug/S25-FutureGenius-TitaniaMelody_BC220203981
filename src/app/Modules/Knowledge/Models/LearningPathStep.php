<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** One step of a learning path: a library resource plus why it is there. */
class LearningPathStep extends Model
{
    protected $fillable = ['learning_resource_id', 'position', 'note'];

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class, 'learning_resource_id');
    }

    /** Members who have completed this step. */
    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_progress')->withPivot('completed_at');
    }
}
