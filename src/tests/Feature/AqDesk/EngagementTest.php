<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Models\ReputationEvent;
use App\Modules\Community\Notifications\BadgeEarned;
use App\Modules\Community\Notifications\NewComment;
use App\Modules\Community\Services\ReputationService;
use App\Modules\Platform\Support\LineDiff;

/**
 * Suite C — comments and edit history (1.8), reputation ledger, badges and
 * privileges (1.9). Cases C1–C10.
 */
class EngagementTest extends AqDeskTestCase
{
    private function adjust($user, int $points): void
    {
        ReputationEvent::create(['user_id' => $user->id, 'type' => 'adjustment', 'points' => $points]);
    }

    /** C1 — commenting on your own question needs no reputation and does not notify you. */
    public function test_c1_comment_on_own_question(): void
    {
        $author = $this->userWithRole('member');
        $q = $this->askQuestionAs($author);

        $this->actingAs($author)->post(route('comments.question', $q), ['body' => 'Adding: I use Laravel 12.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $q->comments()->count());
        $this->assertSame(0, $author->notifications()->where('type', NewComment::class)->count());
        $this->get(route('questions.show', $q))->assertSee('Adding: I use Laravel 12.');
    }

    /** C2 — commenting on others' posts needs 5 reputation; then the author is notified. */
    public function test_c2_comment_privilege_and_notification(): void
    {
        $author = $this->userWithRole('member');
        $newcomer = $this->userWithRole('member');
        $q = $this->askQuestionAs($author);

        $this->actingAs($newcomer)->get(route('questions.show', $q))->assertSee('You need 5 reputation to comment');
        $this->actingAs($newcomer)->post(route('comments.question', $q), ['body' => 'Which version?'])->assertForbidden();

        $this->adjust($newcomer, 10);
        $this->actingAs($newcomer)->post(route('comments.question', $q), ['body' => 'Which version are you on?'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $author->notifications()->where('type', NewComment::class)->count());
    }

    /** C3 — the answerer and the question's author may discuss under the answer. */
    public function test_c3_question_author_may_comment_on_answers(): void
    {
        $author = $this->userWithRole('member');
        $q = $this->askQuestionAs($author);
        $answer = $this->answerQuestionAs($this->userWithRole('expert'), $q);

        $this->actingAs($author)->post(route('comments.answer', $answer), ['body' => 'Does this work on MySQL 5.7?'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $answer->comments()->count());
    }

    /** C4 — only the commenter or a moderator may delete a comment. */
    public function test_c4_comment_deletion(): void
    {
        $author = $this->userWithRole('member');
        $q = $this->askQuestionAs($author);
        $this->actingAs($author)->post(route('comments.question', $q), ['body' => 'A comment to delete later.']);
        $comment = $q->comments()->firstOrFail();

        $this->actingAs($this->otherMember())->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->actingAs($this->admin())->delete(route('comments.destroy', $comment))->assertRedirect();
        $this->assertSame(0, $q->comments()->count());
    }

    /** C5 — editing keeps the previous version; the history page shows what changed. */
    public function test_c5_edit_history(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author, ['title' => 'Original title for history test', 'body' => "Line one stays.\nLine two goes."]);

        $this->actingAs($author)->put(route('questions.update', $q), $this->questionPayload([
            'title' => 'Better title for history test', 'body' => "Line one stays.\nLine three arrives.",
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, $q->revisions()->count());
        $q = $q->fresh(); // a new title means a new slug
        $this->get(route('questions.show', $q))->assertSee('view 1 earlier version');
        $this->get(route('questions.history', $q))->assertOk()
            ->assertSee('Original title for history test')
            ->assertSee('Removed:')->assertSee('Line two goes.')
            ->assertSee('Added:')->assertSee('Line three arrives.');
    }

    /** C6 — saving without changes creates no revision; answers keep history too. */
    public function test_c6_unchanged_edit_and_answer_history(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author);
        $this->actingAs($author)->put(route('questions.update', $q), $this->questionPayload());
        $this->assertSame(0, $q->revisions()->count());

        $expert = $this->userWithRole('expert');
        $answer = $this->answerQuestionAs($expert, $q, 'First wording of this helpful answer.');
        $this->actingAs($expert)->put(route('answers.update', $answer), ['body' => 'Second, clearer wording of this answer.']);

        $this->assertSame(1, $answer->revisions()->count());
        $this->get(route('answers.history', $answer))->assertOk()->assertSee('First wording of this helpful answer.');
    }

    /** C7 — the diff marks added and removed lines. */
    public function test_c7_line_diff(): void
    {
        $diff = LineDiff::compare("a\nb\nc", "a\nc\nd");

        $this->assertSame(
            [['same', 'a'], ['removed', 'b'], ['same', 'c'], ['added', 'd']],
            array_map(fn ($l) => [$l['type'], $l['text']], $diff)
        );
    }

    /** C8 — badges are stored once and announced in-app. */
    public function test_c8_badge_is_stored_and_announced(): void
    {
        $member = $this->userWithRole('member');
        $this->askQuestionAs($member);
        $this->askQuestionAs($member, ['title' => 'A second question from the same member']);

        $this->assertDatabaseHas('user_badges', ['user_id' => $member->id, 'badge' => 'first-question']);
        $this->assertSame(1, $member->notifications()->where('type', BadgeEarned::class)->count(), 'Badges are awarded once.');
    }

    /** C9 — rebuilding the ledger reproduces the same totals. */
    public function test_c9_rebuild_is_consistent(): void
    {
        $service = app(ReputationService::class);
        $before = \App\Modules\Identity\Models\User::all()->mapWithKeys(fn ($u) => [$u->id => $service->points($u)]);

        $this->artisan('community:rebuild-reputation')->assertSuccessful();

        $after = \App\Modules\Identity\Models\User::all()->mapWithKeys(fn ($u) => [$u->id => $service->points($u)]);
        $this->assertEquals($before->all(), $after->all());
    }

    /** C10 — moderators may downvote and comment regardless of reputation. */
    public function test_c10_moderators_have_all_privileges(): void
    {
        $admin = $this->userWithRole('admin');
        $q = $this->askQuestionAs($this->userWithRole('member'));

        $this->actingAs($admin)->post(route('votes.question', $q), ['value' => -1])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('comments.question', $q), ['body' => 'Please add your error message.'])
            ->assertSessionHasNoErrors();
    }
}
