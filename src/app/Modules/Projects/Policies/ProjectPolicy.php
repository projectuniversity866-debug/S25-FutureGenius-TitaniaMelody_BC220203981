<?php

namespace App\Modules\Projects\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use Illuminate\Auth\Access\Response;

/** Who may do what in a project space (FR3, roadmap 3.1). */
class ProjectPolicy
{
    /** Members always; any signed-in member when it is "visible to members"; moderators. Others get 404. */
    public function view(User $user, Project $project): Response
    {
        return $project->roleOf($user) || $project->visibility === 'members' || $user->canModerate()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Owners and collaborators work on an active project (files, feedback, tasks — 3.3 – 3.5). */
    public function contribute(User $user, Project $project): bool
    {
        return ! $project->isArchived() && in_array($project->roleOf($user), ['owner', 'collaborator'], true);
    }

    /** A shared file is deleted by whoever added it, or the owner — while the project is active. */
    public function deleteFile(User $user, Project $project, ProjectFile $file): bool
    {
        return $this->contribute($user, $project) && ($user->id === $file->user_id || $project->roleOf($user) === 'owner');
    }

    /** Settings, archiving and members are the owner's. */
    public function update(User $user, Project $project): bool
    {
        return $project->roleOf($user) === 'owner';
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project) || $user->canModerate();
    }

    /** Any member except the owner may leave. */
    public function leave(User $user, Project $project): bool
    {
        return in_array($project->roleOf($user), ['collaborator', 'viewer'], true);
    }
}
