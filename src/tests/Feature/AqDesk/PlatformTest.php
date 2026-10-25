<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Careers\Notifications\ApplicationStatusUpdated;
use App\Modules\Careers\Notifications\NewApplicant;
use App\Modules\Community\Notifications\NewAnswer;
use App\Modules\Platform\Models\ActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Suite P — shared platform services: notifications, activity log,
 * private files. Cases P1–P8.
 */
class PlatformTest extends AqDeskTestCase
{
    /** P1 — an answer notifies the question's author, in-app and by email. */
    public function test_p1_answer_notifies_the_author_by_database_and_mail(): void
    {
        Notification::fake();
        $author   = $this->member();
        $question = $this->askQuestionAs($author);

        $this->answerQuestionAs($this->otherMember(), $question);

        Notification::assertSentTo($author, NewAnswer::class,
            fn ($n, $channels) => $channels === ['database', 'mail']);
    }

    /** P2 — nobody is notified about their own action. */
    public function test_p2_no_notification_for_answering_your_own_question(): void
    {
        $author   = $this->member();
        $question = $this->askQuestionAs($author);

        $this->answerQuestionAs($author, $question);

        // (A "badge earned" notification is fine — it is not about the answer.)
        $this->assertSame(0, $author->notifications()->where('type', NewAnswer::class)->count());
    }

    /** P3 — applying notifies the poster; a status change notifies the applicant, once. */
    public function test_p3_application_notifications(): void
    {
        $expert = $this->expert();
        $member = $this->member();
        $job    = $this->postJobAs($expert);

        $this->applyAs($member, $job);
        $this->assertSame(1, $expert->notifications()->where('type', NewApplicant::class)->count());

        $application = $job->applications()->firstOrFail();
        $this->actingAs($expert)->put(route('applications.status', $application), ['status' => 'shortlisted']);
        $this->actingAs($expert)->put(route('applications.status', $application), ['status' => 'shortlisted']);

        $updates = $member->notifications()->where('type', ApplicationStatusUpdated::class)->get();
        $this->assertCount(1, $updates, 'Re-saving the same status must not notify again.');
        $this->assertStringContainsString('shortlisted', $updates->first()->data['body']);
    }

    /** P4 — opening a notification marks it read and follows its link; others' are off limits. */
    public function test_p4_open_marks_read_and_is_private(): void
    {
        $author   = $this->member();
        $question = $this->askQuestionAs($author);
        $this->answerQuestionAs($this->otherMember(), $question);

        $notification = $author->notifications()->firstOrFail();

        $this->actingAs($this->otherMember())
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();

        $this->actingAs($author)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('questions.show', $question).'#answer-'.$question->answers()->first()->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** P5 — the bell shows the unread count; "mark all read" clears it. */
    public function test_p5_bell_and_mark_all_read(): void
    {
        $author = $this->member();
        foreach (['First question for the bell test here', 'Second question for the bell test here'] as $title) {
            $this->answerQuestionAs($this->otherMember(), $this->askQuestionAs($author, ['title' => $title]));
        }

        $unread = $author->unreadNotifications()->count();
        $this->assertSame(2, $author->unreadNotifications()->where('type', NewAnswer::class)->count());

        $this->actingAs($author)->get(route('dashboard'))
            ->assertSee('Notifications, '.$unread.' unread')
            ->assertSee('New answer to your question');

        $this->actingAs($author)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $author->unreadNotifications()->count());
    }

    /** P6 — admin actions land in the activity log, which only admins can read. */
    public function test_p6_activity_log_records_admin_actions(): void
    {
        $admin  = $this->admin();
        $target = $this->disposableMember();

        $this->actingAs($admin)->put(route('admin.users.active', $target));
        $this->actingAs($admin)->put(route('admin.users.role', $target), ['role_id' => $this->role('expert')->id]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'user.deactivated', 'user_id' => $admin->id, 'subject_id' => $target->id, 'subject_type' => 'user']);
        $this->assertSame(['from' => 'Member', 'to' => 'Expert / Professional'],
            ActivityLog::where('action', 'user.role_changed')->firstOrFail()->properties);

        $this->actingAs($admin)->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Deactivated the account of '.$target->name);

        $this->actingAs($this->member())->get(route('admin.activity.index'))->assertForbidden();
    }

    /** P7 — CVs are stored privately and only the applicant, poster or admin can download them. */
    public function test_p7_resumes_are_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $expert = $this->expert();
        $member = $this->member();
        $job    = $this->postJobAs($expert);

        $this->applyAs($member, $job, ['resume' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]);
        $application = $job->applications()->firstOrFail();

        Storage::disk('local')->assertExists($application->resume_path);
        Storage::disk('public')->assertMissing($application->resume_path);

        $this->actingAs($member)->get(route('applications.resume', $application))->assertOk()->assertDownload();
        $this->actingAs($expert)->get(route('applications.resume', $application))->assertOk();
        $this->actingAs($this->admin())->get(route('applications.resume', $application))->assertOk();
        $this->actingAs($this->otherMember())->get(route('applications.resume', $application))->assertForbidden();
    }

    /** P8 — moderation actions are logged with a readable description. */
    public function test_p8_moderation_is_logged(): void
    {
        $question = $this->askQuestionAs($this->member(), ['title' => 'A question that a moderator will close']);

        $this->actingAs($this->admin())->put(route('admin.questions.toggle', $question));

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'question.closed',
            'description' => 'Closed the question “A question that a moderator will close”',
        ]);
    }
}
