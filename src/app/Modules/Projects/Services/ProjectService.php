<?php

namespace App\Modules\Projects\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Projects\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Project spaces and their members (FR3, roadmap 3.1). */
class ProjectService
{
    public function __construct(private ActivityLogger $activity, private ProjectFileService $files, private TaskService $tasks) {}

    /** @param  array<string, mixed>  $data  validated ProjectRequest data */
    public function create(User $owner, array $data): Project
    {
        return DB::transaction(function () use ($owner, $data) {
            $project = Project::create($data + ['user_id' => $owner->id, 'slug' => Project::makeSlug($data['name'])]);
            $project->members()->attach($owner->id, ['role' => 'owner']);

            return $project;
        });
    }

    /** @param  array<string, mixed>  $data  validated ProjectRequest data */
    public function update(Project $project, array $data): Project
    {
        $project->update($data + ['slug' => Project::makeSlug($data['name'], $project->id)]);

        return $project;
    }

    public function setArchived(Project $project, bool $archived): Project
    {
        $project->update(['status' => $archived ? 'archived' : 'active']);

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->activity->record('project.deleted', $project, 'Deleted the project “'.$project->name.'”',
            ['members' => $project->members()->count()]);

        $this->files->deleteAllFor($project);
        $project->delete();
    }

    /** @throws ValidationException when trying to change the owner's role */
    public function changeRole(Project $project, User $member, string $role): void
    {
        if ($member->id === $project->user_id) {
            throw ValidationException::withMessages(['role' => 'The owner’s role cannot be changed.']);
        }

        $project->members()->updateExistingPivot($member->id, ['role' => $role]);
        if ($role === 'viewer') {
            $this->tasks->unassign($member, $project);   // viewers cannot work on tasks
        }
    }

    /** Remove a member, or leave the project yourself. The owner cannot leave. */
    public function removeMember(Project $project, User $member): void
    {
        if ($member->id === $project->user_id) {
            throw ValidationException::withMessages(['member' => 'The owner cannot leave or be removed. Delete or archive the project instead.']);
        }

        $project->members()->detach($member->id);
        $this->tasks->unassign($member, $project);
    }

    /**
     * Account deletion (roadmap 2.6): memberships end; each project the
     * person owned passes to its longest-standing collaborator, or is
     * deleted when nobody else works on it.
     */
    public function releaseUser(User $user): void
    {
        Project::where('user_id', $user->id)->get()->each(function (Project $project) use ($user) {
            $heir = $project->members()->wherePivot('role', 'collaborator')->whereKeyNot($user->id)
                ->orderBy('project_members.created_at')->first();
            if (! $heir) {
                $this->files->deleteAllFor($project);
                $project->delete();

                return;
            }
            $project->update(['user_id' => $heir->id]);
            $project->members()->updateExistingPivot($heir->id, ['role' => 'owner']);
        });

        DB::table('project_members')->where('user_id', $user->id)->delete();
        $this->tasks->unassign($user);
    }
}
