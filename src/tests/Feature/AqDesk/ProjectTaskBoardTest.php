<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Projects\Models\ProjectTask;
use App\Modules\Projects\Notifications\TaskAssigned;

/**
 * Suite U — the project task board (3.5). Cases U1–U7.
 */
class ProjectTaskBoardTest extends AqDeskTestCase
{
    use BuildsProjectTeams;

    private function addTask($project, $user, array $data = []): ProjectTask
    {
        $this->actingAs($user)->post(route('projects.tasks.store', $project), $data + ['title' => 'Write the test plan', 'priority' => 'normal'])
            ->assertSessionHasNoErrors();

        return $project->tasks()->latest('id')->firstOrFail();
    }

    /** U1 — a collaborator adds a task; it starts in To do and shows on the board. */
    public function test_u1_add_a_task(): void
    {
        [$project, $owner, $collaborator] = $this->team();
        $task = $this->addTask($project, $collaborator, ['description' => 'Cover every form', 'due_on' => today()->addWeek()->toDateString()]);

        $this->assertSame(['todo', $collaborator->id], [$task->status, $task->created_by]);
        $this->actingAs($owner)->get(route('projects.tasks.index', $project))->assertOk()
            ->assertSee('Write the test plan')->assertSee('Cover every form')->assertSee('Normal priority')->assertSee('Unassigned');
    }

    /** U2 — assigning notifies the assignee (not when you assign yourself); only owners and collaborators can be assigned. */
    public function test_u2_assignment(): void
    {
        [$project, $owner, $collaborator, $viewer] = $this->team();

        $task = $this->addTask($project, $owner, ['assignee_id' => $collaborator->id]);
        $this->assertSame(1, $collaborator->notifications()->where('type', TaskAssigned::class)->count());
        $this->addTask($project, $owner, ['assignee_id' => $owner->id]);
        $this->assertSame(0, $owner->notifications()->where('type', TaskAssigned::class)->count());

        $this->actingAs($owner)->post(route('projects.tasks.store', $project), ['title' => 'For the viewer', 'priority' => 'normal', 'assignee_id' => $viewer->id])
            ->assertSessionHasErrors('assignee_id');
        $this->actingAs($owner)->post(route('projects.tasks.store', $project), ['title' => 'For an outsider', 'priority' => 'normal', 'assignee_id' => $this->admin()->id])
            ->assertSessionHasErrors('assignee_id');

        // Editing without changing the assignee does not notify again.
        $this->actingAs($owner)->put(route('projects.tasks.update', [$project, $task]), ['title' => 'Renamed task', 'priority' => 'high', 'assignee_id' => $collaborator->id]);
        $this->assertSame(['Renamed task', 'high'], [$task->fresh()->title, $task->fresh()->priority]);
        $this->assertSame(1, $collaborator->notifications()->where('type', TaskAssigned::class)->count());
    }

    /** U3 — moving between columns; Done records when; reopening clears it. */
    public function test_u3_move(): void
    {
        [$project, $owner] = $this->team();
        $task = $this->addTask($project, $owner);

        $this->actingAs($owner)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'doing'])->assertRedirect();
        $this->assertSame('doing', $task->fresh()->status);
        $this->actingAs($owner)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'done']);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->actingAs($owner)->get(route('projects.show', $project))->assertSee('1 of 1 task done');

        $this->actingAs($owner)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'todo']);
        $this->assertNull($task->fresh()->completed_at);
        $this->actingAs($owner)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'lost'])->assertSessionHasErrors('status');
    }

    /** U4 — the board orders by priority then due date, flags overdue work, and filters to my tasks. */
    public function test_u4_order_overdue_and_filter(): void
    {
        [$project, $owner, $collaborator] = $this->team();
        $this->addTask($project, $owner, ['title' => 'Low priority chore', 'priority' => 'low']);
        $this->addTask($project, $owner, ['title' => 'Urgent late fix', 'priority' => 'high', 'due_on' => today()->subDays(2)->toDateString(), 'assignee_id' => $collaborator->id]);
        $this->addTask($project, $owner, ['title' => 'Normal soon task', 'priority' => 'normal', 'due_on' => today()->addDay()->toDateString()]);

        $this->actingAs($owner)->get(route('projects.tasks.index', $project))
            ->assertSeeInOrder(['Urgent late fix', 'Normal soon task', 'Low priority chore'])->assertSee('Overdue');
        $this->actingAs($collaborator)->get(route('projects.tasks.index', [$project, 'show' => 'mine']))
            ->assertSee('Urgent late fix')->assertDontSee('Low priority chore');
    }

    /** U5 — viewers and outsiders cannot change the board; archived projects are read-only. */
    public function test_u5_permissions(): void
    {
        [$project, $owner, , $viewer] = $this->team();
        $task = $this->addTask($project, $owner);

        $this->actingAs($viewer)->get(route('projects.tasks.index', $project))->assertOk()->assertSee('Viewers can follow the board');
        $this->actingAs($viewer)->post(route('projects.tasks.store', $project), ['title' => 'Viewer task', 'priority' => 'normal'])->assertForbidden();
        $this->actingAs($viewer)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'done'])->assertForbidden();
        $this->actingAs($this->userWithRole('member'))->get(route('projects.tasks.index', $project))->assertNotFound();

        $this->actingAs($owner)->put(route('projects.archive', $project), ['archived' => 1]);
        $this->actingAs($owner)->put(route('projects.tasks.move', [$project, $task]), ['status' => 'done'])->assertForbidden();
    }

    /** U6 — deleting: the creator or the owner; tasks of other projects are 404. */
    public function test_u6_delete(): void
    {
        [$project, $owner, $collaborator] = $this->team();
        $byOwner = $this->addTask($project, $owner);
        $byCollaborator = $this->addTask($project, $collaborator, ['title' => 'Collaborator task']);

        $this->actingAs($collaborator)->delete(route('projects.tasks.destroy', [$project, $byOwner]))->assertForbidden();
        $this->actingAs($collaborator)->delete(route('projects.tasks.destroy', [$project, $byCollaborator]))->assertRedirect();
        $this->actingAs($owner)->delete(route('projects.tasks.destroy', [$project, $byOwner]))->assertRedirect();
        $this->assertSame(0, $project->tasks()->count());

        $other = $this->team()[0];
        $foreign = $this->addTask($other, $owner);
        $this->actingAs($owner)->get(route('projects.tasks.edit', [$project, $foreign]))->assertNotFound();
    }

    /** U7 — leaving, becoming a viewer or deleting your account unassigns your tasks. */
    public function test_u7_unassign(): void
    {
        [$project, $owner, $collaborator] = $this->team();
        $task = $this->addTask($project, $owner, ['assignee_id' => $collaborator->id]);

        $this->actingAs($owner)->put(route('projects.members.update', [$project, $collaborator]), ['role' => 'viewer']);
        $this->assertNull($task->fresh()->assignee_id);

        $this->actingAs($owner)->put(route('projects.members.update', [$project, $collaborator]), ['role' => 'collaborator']);
        $this->actingAs($owner)->put(route('projects.tasks.update', [$project, $task]), ['title' => $task->title, 'priority' => 'normal', 'assignee_id' => $collaborator->id]);
        $this->actingAs($collaborator)->delete(route('projects.leave', $project));
        $this->assertNull($task->fresh()->assignee_id);
    }
}
