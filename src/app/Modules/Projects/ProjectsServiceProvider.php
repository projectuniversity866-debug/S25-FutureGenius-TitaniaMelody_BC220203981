<?php

namespace App\Modules\Projects;

use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFileVersion;
use App\Modules\Projects\Models\ProjectInvitation;
use App\Modules\Projects\Models\ProjectTask;
use App\Modules\Projects\Models\ProjectThread;
use App\Modules\Projects\Models\ProjectThreadReply;
use App\Modules\Projects\Policies\ProjectPolicy;
use App\Modules\Projects\Services\ProjectService;

/**
 * FR3 Project Collaboration — project spaces with member roles (roadmap
 * 3.1), invitations (3.2), shared files with versions (3.3), feedback
 * threads (3.4) and the task board (3.5).
 */
class ProjectsServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Projects';

    protected function policies(): array
    {
        return [Project::class => ProjectPolicy::class];
    }

    protected function morphMap(): array
    {
        return ['project' => Project::class];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                // Projects this user belongs to, in any role.
                'projects' => fn (User $user) => $user->belongsToMany(
                    Project::class, 'project_members', 'user_id', 'project_id', 'id', 'id', 'projects'
                )->withPivot('role')->withTimestamps(),
            ],
        ];
    }

    /** The projects you belong to and your role in each (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'projects' => fn (User $user) => $user->projects()->get()
                ->map(fn (Project $p) => ['project' => $p->name, 'role' => $p->pivot->role, 'since' => $p->pivot->created_at])->all(),
            'project_files_uploaded' => fn (User $user) => ProjectFileVersion::with('file:id,name')->where('user_id', $user->id)->get()
                ->map(fn (ProjectFileVersion $v) => ['file' => $v->file?->name, 'version' => $v->number, 'uploaded_at' => $v->created_at])->all(),
            'project_feedback' => fn (User $user) => [
                'threads' => ProjectThread::where('user_id', $user->id)->get(['title', 'body', 'status', 'created_at'])->toArray(),
                'replies' => ProjectThreadReply::where('user_id', $user->id)->get(['body', 'created_at'])->toArray(),
            ],
            'project_tasks' => fn (User $user) => ProjectTask::where('created_by', $user->id)->orWhere('assignee_id', $user->id)->get()
                ->map(fn (ProjectTask $t) => [
                    'task' => $t->title, 'status' => $t->status, 'due_on' => $t->due_on?->toDateString(),
                    'created_by_you' => $t->created_by === $user->id, 'assigned_to_you' => $t->assignee_id === $user->id,
                ])->all(),
        ];
    }

    /**
     * Memberships end (owned projects are handed over) and your tasks become
     * unassigned; invitations to your address go. Shared files and feedback
     * stay with the project under the anonymised "Deleted user".
     */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            ProjectInvitation::whereRaw('lower(email) = ?', [mb_strtolower($user->email)])->delete();
            app(ProjectService::class)->releaseUser($user);
        }];
    }
}
