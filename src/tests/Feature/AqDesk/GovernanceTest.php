<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Models\Question;
use App\Modules\Governance\Models\ModerationAction;
use App\Modules\Governance\Models\Report;
use App\Modules\Governance\Notifications\ContentRemoved;
use App\Modules\Governance\Notifications\NewReport;
use App\Modules\Governance\Notifications\ReportResolved;

/**
 * Suite G — governance (2.5): reporting, the moderation queue, decisions and
 * the moderation log, community guidelines. Cases G1–G8.
 */
class GovernanceTest extends AqDeskTestCase
{
    private function reportQuestion($reporter, Question $q, array $data = ['reason' => 'spam'])
    {
        return $this->actingAs($reporter)->post(route('reports.store', ['question', $q->id]), $data);
    }

    /** G1 — a member reports a question; moderators are notified; the report is open. */
    public function test_g1_report_content(): void
    {
        $q = $this->askQuestionAs($this->member(), ['title' => 'Buy cheap followers here today now']);

        $this->actingAs($this->otherMember())->get(route('reports.create', ['question', $q->id]))->assertOk()
            ->assertSee('Buy cheap followers here today now')->assertSee('Spam or advertising');
        $this->reportQuestion($this->otherMember(), $q)->assertRedirect(route('questions.show', $q))->assertSessionHas('status');

        $this->assertDatabaseHas('reports', ['reportable_type' => 'question', 'reportable_id' => $q->id, 'status' => 'open']);
        $this->assertSame(1, $this->admin()->notifications()->where('type', NewReport::class)->count());
    }

    /** G2 — no reporting your own content, twice, or without a reason; "other" needs details. */
    public function test_g2_report_rules(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author);

        $this->reportQuestion($author, $q)->assertSessionHasErrors(['reason' => 'You cannot report your own content.']);
        $this->reportQuestion($this->otherMember(), $q);
        $this->reportQuestion($this->otherMember(), $q)->assertSessionHasErrors('reason');
        $this->reportQuestion($this->expert(), $q, [])->assertSessionHasErrors('reason');
        $this->reportQuestion($this->expert(), $q, ['reason' => 'other'])->assertSessionHasErrors('details');
        $this->assertSame(1, Report::where('reportable_type', 'question')->where('reportable_id', $q->id)->count());

        $this->signOut();
        $this->get(route('reports.create', ['question', $q->id]))->assertRedirect(route('login'));
        $this->actingAs($this->expert())->get(route('reports.create', ['user', 1]))->assertNotFound();
    }

    /** G3 — the report link shows on others' content only. */
    public function test_g3_report_link(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author);
        $link = route('reports.create', ['question', $q->id]);

        $this->actingAs($this->otherMember())->get(route('questions.show', $q))->assertSee($link, false);
        $this->actingAs($author)->get(route('questions.show', $q))->assertDontSee($link, false);
    }

    /** G4 — removing deletes the content through its module, resolves every report on it, and tells everyone. */
    public function test_g4_remove_decision(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author, ['title' => 'A question that breaks the rules badly']);
        $this->reportQuestion($this->otherMember(), $q);
        $this->reportQuestion($this->expert(), $q, ['reason' => 'abuse']);
        $report = Report::where('reportable_id', $q->id)->where('reportable_type', 'question')->oldest('id')->firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.reports.show', $report))->assertOk()->assertSee('Reports (2)');
        $this->actingAs($this->admin())->put(route('admin.reports.decide', $report), ['decision' => 'remove', 'note' => 'Selling followers is spam.'])
            ->assertRedirect(route('admin.reports.index'));

        $this->assertDatabaseMissing('questions', ['id' => $q->id]);
        $this->assertSame(2, Report::where('reportable_id', $q->id)->where('status', 'actioned')->count());
        $this->assertDatabaseHas('moderation_actions', ['action' => 'removed', 'reports_count' => 2,
            'subject_label' => 'Question “A question that breaks the rules badly”']);
        $this->assertStringContainsString('Selling followers is spam.',
            $author->notifications()->where('type', ContentRemoved::class)->firstOrFail()->data['body']);
        $this->assertSame(1, $this->otherMember()->notifications()->where('type', ReportResolved::class)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'moderation.removed']);
    }

    /** G5 — dismissing keeps the content; a decided report cannot be decided again. */
    public function test_g5_dismiss_decision(): void
    {
        $q = $this->askQuestionAs($this->member());
        $this->reportQuestion($this->otherMember(), $q);
        $report = Report::where('reportable_id', $q->id)->where('reportable_type', 'question')->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.reports.decide', $report), ['decision' => 'dismiss']);
        $this->assertDatabaseHas('questions', ['id' => $q->id]);
        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertStringContainsString('within the community guidelines',
            $this->otherMember()->notifications()->where('type', ReportResolved::class)->firstOrFail()->data['body']);

        $this->actingAs($this->admin())->put(route('admin.reports.decide', $report), ['decision' => 'remove'])
            ->assertSessionHasErrors('decision');
    }

    /** G6 — answers, comments, articles and opportunities are reportable too. */
    public function test_g6_other_content_types(): void
    {
        $q = $this->askQuestionAs($this->member());
        $answer = $this->answerQuestionAs($this->expert(), $q);
        $this->actingAs($this->member())->post(route('comments.question', $q), ['body' => 'Clarifying my own question here.']);
        $comment = $q->comments()->firstOrFail();
        $job = $this->postJobAs($this->expert());

        foreach ([['answer', $answer->id], ['comment', $comment->id], ['job_post', $job->id]] as [$type, $id]) {
            $this->actingAs($this->otherMember())->post(route('reports.store', [$type, $id]), ['reason' => 'off_topic'])
                ->assertSessionHasNoErrors();
        }
        $article = \App\Modules\Knowledge\Models\Article::firstOrFail();
        $this->actingAs($this->otherMember())->post(route('reports.store', ['article', $article->id]), ['reason' => 'misinformation'])
            ->assertSessionHasNoErrors();

        $comment = Report::where('reportable_type', 'comment')->firstOrFail();
        $this->actingAs($this->admin())->put(route('admin.reports.decide', $comment), ['decision' => 'remove']);
        $this->assertSame(0, $q->comments()->count());
    }

    /** G7 — only moderators see the queue and the log; the log keeps removed content's label. */
    public function test_g7_queue_and_log_access(): void
    {
        $this->actingAs($this->member())->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->expert())->get(route('admin.moderation.log'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.reports.index'))->assertOk()
            ->assertSee('Answer to “What is the cleanest way'); // seeded report
        ModerationAction::forceCreate(['moderator_id' => $this->admin()->id, 'subject_type' => 'question', 'subject_id' => 999,
            'subject_label' => 'Question “Gone but logged”', 'action' => 'removed', 'reason' => 'spam']);
        $this->actingAs($this->admin())->get(route('admin.moderation.log'))->assertOk()->assertSee('Question “Gone but logged”');
    }

    /** G8 — the guidelines, terms and privacy pages are public and linked from every page. */
    public function test_g8_public_pages(): void
    {
        foreach (['guidelines' => 'Community guidelines', 'terms' => 'Terms of use', 'privacy' => 'Privacy policy'] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title);
        }
        $this->get(route('home'))->assertSee(route('guidelines'), false)->assertSee(route('privacy'), false);
    }
}
