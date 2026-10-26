<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Models\User;
use App\Modules\Projects\Http\Requests\ProjectMemberRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** A project's members and their roles (roadmap 3.1). New members join through invitations (3.2). */
class ProjectMemberController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    public function update(ProjectMemberRequest $request, Project $project, User $member): RedirectResponse
    {
        $this->projects->changeRole($project, $member, $request->validated('role'));

        return redirect()->to(route('projects.show', $project).'#members')->with('status', $member->name.'’s role was changed.');
    }

    public function destroy(Project $project, User $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $this->projects->removeMember($project, $member);

        return redirect()->to(route('projects.show', $project).'#members')->with('status', $member->name.' was removed from the project.');
    }

    public function leave(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('leave', $project);

        $this->projects->removeMember($project, $request->user());

        return redirect()->route('projects.index')->with('status', 'You left “'.$project->name.'”.');
    }
}
