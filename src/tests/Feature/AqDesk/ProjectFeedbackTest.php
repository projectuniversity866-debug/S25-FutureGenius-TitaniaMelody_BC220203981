<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Projects\Models\ProjectThread;
use App\Modules\Projects\Notifications\FeedbackPosted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Suite N — project feedback threads (3.4). Cases N1–N7.
 */
class ProjectFeedbackTest extends AqDeskTestCase
{
    use BuildsProjectTeams;

    private function start($project, $user, array $data = []): ProjectThread
    {
        $this->actingAs($user)->post(route('projects.threads.store', $project), $data + [
            'title' => 'Feedback on chapter two', 'body' => 'The **method** section needs a diagram.',
        ])->assertSessionHasNoErrors();

        return $project->threads()->firstOrFail();
    }

    /** N1 — any member, viewers included, starts a thread; the others are notified, not the author. */
    public function test_n1_start_a_thread(): void
    {
        [$project, $owner, $collaborator, $viewer] = $this->team();
        $thread = $this->start($project, $viewer);

        $this->assertSame('open', $thread->status);
        foreach ([$owner, $collaborator] as $u) {
            $this->assertSame(1, $u->notifications()->where('type', FeedbackPosted::class)->count());
        }
        $this->assertSame(0, $viewer->notifications()->where('type', FeedbackPosted::class)->count());
        $this->actingAs($owner)->get(route('projects.threads.show', [$project, $thread]))
            ->assertOk()->assertSee('Feedback on chapter two')->assertSee('<strong>method</strong>', false);
        $this->actingAs($owner)->get(route('projects.threads.index', $project))->assertSee('Feedback on chapter two');

        // Email for project notices is on by default and can be switched off in Account settings.
        $this->assertSame(['database', 'mail'], (new FeedbackPosted($thread, 'x', false))->via($owner));
        $this->actingAs($owner)->get(route('settings.show'))->assertSee('Feedback in my projects');
        $this->actingAs($owner)->put(route('settings.update'), ['profile_visibility' => 'public']);
        $this->assertSame(['database'], (new FeedbackPosted($thread, 'x', false))->via($owner->fresh()));
    }

    /** N2 — replies are listed in order and notify the people in the thread. */
    public function test_n2_replies(): void
    {
        [$project, $owner, $collaborator, $viewer] = $this->team();
        $thread = $this->start($project, $viewer);
        $owner->notifications()->delete();

        $this->actingAs($collaborator)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'First reply here'])->assertRedirect();
        $this->actingAs($owner)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'Second reply here']);

        $this->assertSame(2, $thread->fresh()->replies_count);
        $this->assertSame(2, $viewer->notifications()->where('type', FeedbackPosted::class)->count());       // author: both replies
        $this->assertSame(2, $collaborator->notifications()->where('type', FeedbackPosted::class)->count()); // the new thread + the owner's reply
        $this->assertSame(0, $owner->notifications()->where('type', FeedbackPosted::class)->count());        // was not in the thread before replying
        $this->actingAs($owner)->get(route('projects.threads.show', [$project, $thread]))->assertSeeInOrder(['First reply here', 'Second reply here']);
    }

    /** N3 — resolving closes the thread to replies; reopening allows them again; only author or owner may. */
    public function test_n3_resolve_and_reopen(): void
    {
        [$project, $owner, $collaborator, $viewer] = $this->team();
        $thread = $this->start($project, $viewer);

        $this->actingAs($collaborator)->put(route('projects.threads.resolve', [$project, $thread]), ['resolved' => 1])->assertForbidden();
        $this->actingAs($viewer)->put(route('projects.threads.resolve', [$project, $thread]), ['resolved' => 1]);
        $this->assertTrue($thread->fresh()->isResolved());
        $this->assertSame($viewer->id, $thread->fresh()->resolved_by);

        $this->actingAs($collaborator)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'Too late?'])->assertSessionHasErrors('body');
        $this->actingAs($owner)->get(route('projects.threads.index', [$project, 'tab' => 'resolved']))->assertSee('Feedback on chapter two');

        $this->actingAs($owner)->put(route('projects.threads.resolve', [$project, $thread]), ['resolved' => 0]);
        $this->assertFalse($thread->fresh()->isResolved());
        $this->actingAs($collaborator)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'Now it works'])->assertSessionHasNoErrors();
    }

    /** N4 — feedback about a file is linked from the file page; files of other projects are refused. */
    public function test_n4_feedback_on_a_file(): void
    {
        Storage::fake('local');
        [$project, $owner] = $this->team();
        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('draft.pdf', 10, 'application/pdf')]);
        $file = $project->files()->firstOrFail();

        $thread = $this->start($project, $owner, ['project_file_id' => $file->id]);
        $this->assertSame($file->id, $thread->project_file_id);
        $this->actingAs($owner)->get(route('projects.files.show', [$project, $file]))->assertSee('Feedback on chapter two');
        $this->actingAs($owner)->get(route('projects.threads.show', [$project, $thread]))->assertSee('About draft.pdf');

        $other = $this->team()[0]->files()->create(['name' => 'Another project’s file']);
        $this->actingAs($owner)->post(route('projects.threads.store', $project), ['title' => 'Wrong file', 'body' => 'Should fail', 'project_file_id' => $other->id])
            ->assertSessionHasErrors('project_file_id');
    }

    /** N5 — outsiders cannot see or post; archived projects take no feedback; validation. */
    public function test_n5_access(): void
    {
        [$project, $owner] = $this->team();
        $thread = $this->start($project, $owner);
        $outsider = $this->userWithRole('member');

        $this->actingAs($outsider)->get(route('projects.threads.show', [$project, $thread]))->assertNotFound();
        $this->actingAs($outsider)->post(route('projects.threads.store', $project), ['title' => 'Hello there', 'body' => 'Let me in'])->assertForbidden();
        $this->actingAs($owner)->post(route('projects.threads.store', $project), ['title' => 'x', 'body' => ''])->assertSessionHasErrors(['title', 'body']);

        $this->actingAs($owner)->put(route('projects.archive', $project), ['archived' => 1]);
        $this->actingAs($owner)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'Archived now'])->assertForbidden();
        $this->actingAs($owner)->get(route('projects.threads.show', [$project, $thread]))->assertOk()->assertDontSee('Post reply');
    }

    /** N6 — replies are deleted by their author or the owner; threads by their author or the owner. */
    public function test_n6_deleting(): void
    {
        [$project, $owner, $collaborator, $viewer] = $this->team();
        $thread = $this->start($project, $viewer);
        $this->actingAs($collaborator)->post(route('projects.threads.reply', [$project, $thread]), ['body' => 'A reply to delete']);
        $reply = $thread->replies()->firstOrFail();

        $this->actingAs($viewer)->delete(route('projects.threads.replies.destroy', [$project, $thread, $reply]))->assertForbidden();
        $this->actingAs($owner)->delete(route('projects.threads.replies.destroy', [$project, $thread, $reply]))->assertRedirect();
        $this->assertSame(0, $thread->fresh()->replies_count);

        $this->actingAs($collaborator)->delete(route('projects.threads.destroy', [$project, $thread]))->assertForbidden();
        $this->actingAs($viewer)->delete(route('projects.threads.destroy', [$project, $thread]))->assertRedirect(route('projects.threads.index', $project));
        $this->assertDatabaseMissing('project_threads', ['id' => $thread->id]);
    }

    /** N7 — the project page shows open feedback; the export contains your threads and replies. */
    public function test_n7_overview_and_export(): void
    {
        [$project, $owner] = $this->team();
        $this->start($project, $owner);

        $this->actingAs($owner)->get(route('projects.show', $project))->assertSee('Open feedback')->assertSee('Feedback on chapter two');
        $data = $this->actingAs($owner)->get(route('privacy.export'))->assertOk()->json('data');
        $this->assertSame(['Feedback on chapter two'], array_column($data['project_feedback']['threads'], 'title'));
        $this->assertArrayHasKey('project_tasks', $data);
    }
}
