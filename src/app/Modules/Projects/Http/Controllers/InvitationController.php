<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Http\Requests\ProjectMemberRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectInvitation;
use App\Modules\Projects\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FR3 "Invite collaborators" (roadmap 3.2). */
class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function store(ProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $invitation = $this->invitations->invite($project, $request->user(), $request->validated('email'), $request->validated('role'));

        return redirect()->to(route('projects.show', $project).'#members')
            ->with('status', 'Invitation sent to '.$invitation->email.'. It is valid for '.ProjectInvitation::VALID_DAYS.' days.');
    }

    public function resend(Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorize('manageMembers', $project);
        abort_unless($invitation->project_id === $project->id, 404);

        $this->invitations->resend($invitation);

        return redirect()->to(route('projects.show', $project).'#members')->with('status', 'A new invitation link was sent to '.$invitation->email.'.');
    }

    public function cancel(Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorize('manageMembers', $project);
        abort_unless($invitation->project_id === $project->id, 404);

        $this->invitations->cancel($invitation);

        return redirect()->to(route('projects.show', $project).'#members')->with('status', 'Invitation cancelled; its link no longer works.');
    }

    /** The page behind the emailed link. Anyone holding the link may see it. */
    public function show(Request $request, string $token): View
    {
        // A visitor who signs in (or registers) from here comes back to this page.
        if (! $request->user()) {
            redirect()->setIntendedUrl($request->fullUrl());
        }

        return view('projects::invitation', ['invitation' => $this->invitations->fromToken($token), 'token' => $token]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitations->fromToken($token);
        $this->invitations->accept($invitation, $request->user());

        return redirect()->route('projects.show', $invitation->project)
            ->with('status', 'Welcome to “'.$invitation->project->name.'”.');
    }

    public function decline(string $token): RedirectResponse
    {
        $invitation = $this->invitations->fromToken($token);
        $this->invitations->decline($invitation);

        return redirect()->route('invitations.show', $token)->with('status', 'Invitation declined. The owner has been told.');
    }
}
