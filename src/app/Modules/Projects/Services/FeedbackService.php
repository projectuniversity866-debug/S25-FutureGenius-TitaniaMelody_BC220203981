<?php

namespace App\Modules\Projects\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectThread;
use App\Modules\Projects\Models\ProjectThreadReply;
use App\Modules\Projects\Notifications\FeedbackPosted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * FR3 "Provide feedback" (roadmap 3.4). A new thread notifies the other
 * members; a reply notifies the people already in the thread. Resolved
 * threads take no replies until reopened.
 */
class FeedbackService
{
    /** @param  array{title: string, body: string, project_file_id?: int|null}  $data */
    public function start(Project $project, User $author, array $data): ProjectThread
    {
        $thread = $project->threads()->create([
            'user_id'          => $author->id,
            'project_file_id'  => $data['project_file_id'] ?? null,
            'title'            => $data['title'],
            'body'             => $data['body'],
            'last_activity_at' => now(),
        ]);

        $others = $project->members()->where('users.id', '!=', $author->id)->where('is_active', true)->get();
        Notification::send($others, new FeedbackPosted($thread, $author->name, false));

        return $thread;
    }

    /** @throws ValidationException when the thread is resolved */
    public function reply(ProjectThread $thread, User $author, string $body): ProjectThreadReply
    {
        if ($thread->isResolved()) {
            throw ValidationException::withMessages(['body' => 'This thread is resolved. Reopen it to reply.']);
        }

        $reply = DB::transaction(function () use ($thread, $author, $body) {
            $reply = $thread->replies()->create(['user_id' => $author->id, 'body' => $body]);
            $thread->increment('replies_count', 1, ['last_activity_at' => now()]);

            return $reply;
        });

        Notification::send($this->participants($thread)->reject(fn (User $u) => $u->id === $author->id),
            new FeedbackPosted($thread, $author->name, true));

        return $reply;
    }

    public function setResolved(ProjectThread $thread, User $by, bool $resolved): ProjectThread
    {
        $thread->update($resolved
            ? ['status' => 'resolved', 'resolved_by' => $by->id, 'resolved_at' => now()]
            : ['status' => 'open', 'resolved_by' => null, 'resolved_at' => null]);

        return $thread;
    }

    public function deleteThread(ProjectThread $thread): void
    {
        $thread->delete();
    }

    public function deleteReply(ProjectThreadReply $reply): void
    {
        DB::transaction(function () use ($reply) {
            $reply->delete();
            $reply->thread()->where('replies_count', '>', 0)->decrement('replies_count');
        });
    }

    /** The thread's author and everyone who replied, who are still active members. */
    private function participants(ProjectThread $thread): Collection
    {
        $ids = $thread->replies()->pluck('user_id')->push($thread->user_id)->filter()->unique();

        return $thread->project->members()->whereIn('users.id', $ids)->where('is_active', true)->get();
    }
}
