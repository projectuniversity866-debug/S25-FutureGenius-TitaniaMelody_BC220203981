<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Notifications\ProjectInvitationSent;

/**
 * Suite J — project spaces with member roles (3.1). Cases J1–J7.
 */
class ProjectSpaceTest extends AqDeskTestCase
{
    private function createProject($owner, array $data = []): Project
    {
        $this->actingAs($owner)->post(route('projects.store'), array_merge([
            'name' => 'Capstone team space', 'description' => 'Where our team plans the capstone.', 'visibility' => 'private',
        ], $data))->assertSessionHasNoErrors();

        return Project::where('user_id', $owner->id)->latest('id')->firstOrFail();
    }

    /** J1 — any member creates a project and becomes its owner. */
    public function test_j1_create(): void
    {
        $owner = $this->member();
        $project = $this->createProject($owner);

        $this->assertSame('owner', $project->roleOf($owner));
        $this->actingAs($owner)->get(route('projects.show', $project))->assertOk()
            ->assertSee('Capstone team space')->assertSee('You: Owner');
        $this->actingAs($owner)->get(route('projects.index'))->assertSee('Capstone team space');
        $this->actingAs($owner)->post(route('projects.store'), ['name' => 'x', 'visibility' => 'public'])
            ->assertSessionHasErrors(['name', 'visibility']);
    }

    /** Invite someone and accept as them (the invitation flow itself is tested in suite Q). */
    private function join(Project $project, $owner, $user, string $role): void
    {
        $this->actingAs($owner)->post(route('projects.invitations.store', $project), ['email' => $user->email, 'role' => $role])
            ->assertSessionHasNoErrors();
        $url = $user->notifications()->where('type', ProjectInvitationSent::class)->latest()->firstOrFail()->data['url'];
        $this->actingAs($user)->post(rtrim($url, '/').'/accept')->assertSessionHasNoErrors();
    }

    /** J2 — members join in the role they were invited with; the owner cannot be invited as owner. */
    public function test_j2_members_join_by_invitation(): void
    {
        $owner = $this->member();
        $project = $this->createProject($owner);
        $sara = $this->otherMember();

        $this->join($project, $owner, $sara, 'collaborator');
        $this->assertSame('collaborator', $project->roleOf($sara));
        $this->actingAs($owner)->post(route('projects.invitations.store', $project), ['email' => $this->expert()->email, 'role' => 'owner'])
            ->assertSessionHasErrors('role');
    }

    /** J3 — private projects are hidden (404) from non-members; "members" projects are discoverable. */
    public function test_j3_visibility(): void
    {
        $private = $this->createProject($this->member());
        $open = $this->createProject($this->member(), ['name' => 'Open study circle', 'visibility' => 'members']);

        $this->actingAs($this->otherMember())->get(route('projects.show', $private))->assertNotFound();
        $this->actingAs($this->otherMember())->get(route('projects.show', $open))->assertOk();
        $this->actingAs($this->otherMember())->get(route('projects.index', ['tab' => 'discover']))
            ->assertSee('Open study circle')->assertDontSee('Capstone team space');
        $this->signOut();
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    /** J4 — only the owner manages; roles change; the owner's role cannot. */
    public function test_j4_roles(): void
    {
        $owner = $this->member();
        $project = $this->createProject($owner);
        $sara = $this->otherMember();
        $this->join($project, $owner, $sara, 'viewer');

        $this->actingAs($sara)->put(route('projects.update', $project), ['name' => 'Hijacked', 'visibility' => 'private'])->assertForbidden();
        $this->actingAs($sara)->post(route('projects.invitations.store', $project), ['email' => $this->expert()->email, 'role' => 'viewer'])->assertForbidden();

        $this->actingAs($owner)->put(route('projects.members.update', [$project, $sara]), ['role' => 'collaborator']);
        $this->assertSame('collaborator', $project->fresh()->roleOf($sara));
        $this->actingAs($owner)->put(route('projects.members.update', [$project, $owner]), ['role' => 'viewer'])->assertSessionHasErrors('role');

        $this->assertTrue($sara->can('contribute', $project->fresh()));
    }

    /** J5 — members leave; the owner removes; the owner can neither leave nor be removed. */
    public function test_j5_leave_and_remove(): void
    {
        $owner = $this->member();
        $project = $this->createProject($owner);
        $sara = $this->otherMember();
        $expert = $this->expert();
        foreach ([$sara, $expert] as $u) {
            $this->join($project, $owner, $u, 'collaborator');
        }

        $this->actingAs($sara)->delete(route('projects.leave', $project))->assertRedirect(route('projects.index'));
        $this->assertNull($project->fresh()->roleOf($sara));
        $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $expert]));
        $this->assertNull($project->fresh()->roleOf($expert));
        $this->actingAs($owner)->delete(route('projects.leave', $project))->assertForbidden();
        $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $owner]))->assertSessionHasErrors('member');
    }

    /** J6 — archiving makes it read-only for contributions; deleting is logged. */
    public function test_j6_archive_and_delete(): void
    {
        $owner = $this->member();
        $project = $this->createProject($owner);

        $this->actingAs($owner)->put(route('projects.archive', $project), ['archived' => 1]);
        $this->assertTrue($project->fresh()->isArchived());
        $this->assertFalse($owner->can('contribute', $project->fresh()));
        $this->actingAs($owner)->get(route('projects.show', $project))->assertSee('archived and read-only');

        $this->actingAs($owner)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project.deleted']);
    }

    /** J7 — deleting your account hands owned projects to a collaborator (or deletes them). */
    public function test_j7_account_deletion(): void
    {
        $owner = $this->userWithRole('member');
        $kept = $this->createProject($owner, ['name' => 'Team project with a collaborator']);
        $alone = $this->createProject($owner, ['name' => 'Solo notes project']);
        $sara = $this->otherMember();
        $this->join($kept, $owner, $sara, 'collaborator');

        $this->actingAs($owner)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);

        $this->assertSame($sara->id, $kept->fresh()->user_id);
        $this->assertSame('owner', $kept->fresh()->roleOf($sara));
        $this->assertDatabaseMissing('projects', ['id' => $alone->id]);
    }
}
