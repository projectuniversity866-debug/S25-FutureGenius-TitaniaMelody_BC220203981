<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Notifications\ConfirmSubscription;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Governance\Models\Report;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningResource;
use App\Modules\Platform\Models\ActivityLog;
use App\Modules\Platform\Models\Form;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Suite GP — paths the module suites did not reach, found with a code
 * coverage run (4.1): editing and deleting curated content, a moderator
 * removing an article, form settings and deletion, pruning the activity
 * log, removing a day off, deleting drafts, two sign-in pages. Cases GP1–GP5.
 */
class ModuleGapsTest extends AqDeskTestCase
{
    /** GP1 — Knowledge: a curator edits a resource and deletes a path; a moderator removes a reported article. */
    public function test_gp1_knowledge_curation_and_moderation(): void
    {
        $expert = $this->expert();
        $resource = LearningResource::firstOrFail();
        $this->actingAs($expert)->get(route('learning.resources.edit', $resource))->assertOk()->assertSee($resource->title);
        $this->actingAs($expert)->put(route('learning.resources.update', $resource), [
            'title' => 'Renamed resource for the gap test', 'kind' => $resource->kind, 'level' => $resource->level, 'url' => $resource->url,
            'provider' => $resource->provider, 'description' => 'A description long enough to pass validation.', 'minutes' => 30,
            'category_id' => $resource->category_id, 'is_free' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Renamed resource for the gap test', $resource->fresh()->title);

        $this->actingAs($expert)->get(route('learning.paths.create'))->assertOk();
        $this->actingAs($expert)->post(route('learning.paths.store'), ['title' => 'A path that will be deleted', 'summary' => 'Created only to check that deleting works.', 'level' => 'beginner']);
        $path = LearningPath::where('title', 'A path that will be deleted')->firstOrFail();
        $this->actingAs($expert)->get(route('learning.paths.edit', $path))->assertOk();
        $this->actingAs($this->otherMember())->delete(route('learning.paths.destroy', $path))->assertForbidden();
        $this->actingAs($expert)->delete(route('learning.paths.destroy', $path))->assertRedirect();
        $this->assertModelMissing($path);

        $article = Article::published()->firstOrFail();
        $this->actingAs($article->author)->get(route('articles.mine'))->assertOk()->assertSee($article->title);
        $this->actingAs($article->author)->get(route('articles.edit', $article))->assertOk();
        $this->actingAs($this->member())->post(route('reports.store', ['article', $article->id]), ['reason' => 'spam'])->assertSessionHasNoErrors();
        $report = Report::where('reportable_type', 'article')->where('reportable_id', $article->id)->firstOrFail();
        $this->actingAs($this->admin())->get(route('admin.reports.show', $report))->assertOk()->assertSee($article->title);
        $this->actingAs($this->admin())->put(route('admin.reports.decide', $report), ['decision' => 'remove', 'note' => 'Advertising.']);
        $this->assertModelMissing($article);
        $this->assertDatabaseHas('moderation_actions', ['action' => 'removed']);
    }

    /** GP2 — Platform: form settings change, only the creator deletes; the notifications page; old activity is pruned. */
    public function test_gp2_forms_notifications_and_pruning(): void
    {
        $expert = $this->expert();
        $this->actingAs($expert)->post(route('forms.store'), ['title' => 'Feedback after the workshop']);
        $form = Form::latest('id')->firstOrFail();

        $this->actingAs($expert)->put(route('forms.update', $form), ['title' => 'Workshop feedback', 'requires_login' => '1', 'confirmation_message' => 'Thanks for helping us improve.'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Workshop feedback', true], [$form->fresh()->title, (bool) $form->fresh()->requires_login]);
        $this->actingAs($this->otherExpert())->delete(route('forms.destroy', $form))->assertForbidden();
        $this->actingAs($expert)->delete(route('forms.destroy', $form))->assertRedirect(route('forms.index'));
        $this->assertModelMissing($form);

        $question = $this->askQuestionAs($this->member());
        $this->answerQuestionAs($this->otherMember(), $question);
        $this->actingAs($this->member())->get(route('notifications.index'))->assertOk()->assertSee('New answer');

        $old = ActivityLog::create(['action' => 'test.old', 'description' => 'A year and a day ago']);
        $old->forceFill(['created_at' => now()->subDays(ActivityLog::RETENTION_DAYS + 1)])->save();
        $this->artisan('model:prune', ['--model' => ActivityLog::class])->assertSuccessful();
        $this->assertModelMissing($old);
    }

    /** GP3 — Consulting: a consultant removes their own day off (nobody else can); drafts are deleted; edit pages open. */
    public function test_gp3_consulting_edits(): void
    {
        $expert = $this->otherExpert();
        $this->actingAs($expert)->put(route('consulting.profile.update'), [
            'headline' => 'Code reviews for final-year projects', 'session_minutes' => 60, 'timezone' => 'Asia/Karachi', 'notice_hours' => 0, 'window_days' => 14, 'is_accepting' => 1,
        ]);
        $profile = ConsultantProfile::where('user_id', $expert->id)->firstOrFail();
        $this->actingAs($expert)->post(route('consulting.days-off.store'), ['date' => now()->addWeek()->toDateString(), 'reason' => 'Exams']);
        $day = $profile->daysOff()->firstOrFail();

        $this->actingAs($this->expert())->delete(route('consulting.days-off.destroy', $day))->assertNotFound();
        $this->actingAs($expert)->delete(route('consulting.days-off.destroy', $day))->assertRedirect();
        $this->assertModelMissing($day);

        $this->actingAs($expert)->get(route('consulting.clients.create'))->assertOk();
        $this->actingAs($expert)->post(route('consulting.clients.store'), ['name' => 'Hina Qureshi', 'status' => 'lead']);
        $client = $profile->clients()->firstOrFail();
        $this->actingAs($expert)->get(route('consulting.clients.edit', $client))->assertOk()->assertSee('Hina Qureshi');
        $this->actingAs($expert)->post(route('consulting.proposals.store', $client), ['title' => 'A draft to throw away', 'body' => 'Nothing in here will ever be sent to anyone.']);
        $proposal = Proposal::firstOrFail();
        $this->actingAs($expert)->get(route('consulting.proposals.edit', $proposal))->assertOk()->assertSee('A draft to throw away');
        $this->actingAs($expert)->delete(route('consulting.proposals.destroy', $proposal))->assertRedirect(route('consulting.clients.show', $client));
        $this->assertModelMissing($proposal);
    }

    /** GP4 — Communications: drafts can be deleted, sent campaigns cannot; the confirmation email says what to do. */
    public function test_gp4_communications(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.newsletter.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.announcements.create'))->assertOk();
        foreach (['Draft to delete later', 'Campaign that gets sent'] as $subject) {
            $this->actingAs($admin)->post(route('admin.newsletter.store'), ['subject' => $subject, 'body' => 'A body that is long enough to save.']);
        }
        [$draft, $sent] = Campaign::orderBy('id')->get()->all();
        $this->actingAs($admin)->put(route('admin.newsletter.send', $sent));
        $this->actingAs($admin)->delete(route('admin.newsletter.destroy', $sent))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.newsletter.destroy', $draft))->assertRedirect(route('admin.newsletter.index'));
        $this->assertModelMissing($draft);

        $subscriber = Subscriber::create(['email' => 'reader@example.com', 'status' => 'pending', 'token' => Subscriber::newToken()]);
        $mail = (new ConfirmSubscription($subscriber))->toMail(new AnonymousNotifiable);
        $this->assertSame('Confirm your AQDesk newsletter subscription', $mail->subject);
        $this->assertSame(route('newsletter.confirm', $subscriber->token), $mail->actionUrl);
        $this->assertStringContainsString(Subscriber::CONFIRM_DAYS.' days', implode(' ', $mail->outroLines));
    }

    /** GP5 — Identity: the "confirm your email" page for an unconfirmed account, and the forgot-password page. */
    public function test_gp5_identity_pages(): void
    {
        $user = $this->disposableMember();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('Confirm your email');
        $this->actingAs($this->member())->get(route('verification.notice'))->assertRedirect();   // already confirmed
        $this->signOut();
        $this->get(route('password.request'))->assertOk()->assertSee('Email me a reset link');
    }
}
