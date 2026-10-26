<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records audit-trail entries. Any module can call it:
 *
 *     $activity->record('question.closed', $question, 'Closed “'.$question->title.'”');
 */
class ActivityLogger
{
    /** @param  array<string, mixed>  $properties */
    public function record(string $action, ?Model $subject, string $description, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'properties'   => $properties ?: null,
            'ip_address'   => request()?->ip(),
        ]);
    }
}
