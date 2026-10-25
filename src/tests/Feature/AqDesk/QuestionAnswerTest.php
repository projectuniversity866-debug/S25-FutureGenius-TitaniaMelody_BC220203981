<?php

namespace Tests\Feature\AqDesk;

/**
 * Suite E — Community Q&A (Module 5). Cases E1–E17.
 */
class QuestionAnswerTest extends AqDeskTestCase
{
    /** E1 — a question saves with its category and tags. */
    public function test_e1_question_is_created_with_category_and_tags(): void
    {
        $question = $this->askQuestionAs($this->member(), [
            'tags' => 'laravel, middleware, rbac',
        ]);

        $this->assertNotNull($question->category);
        $this->assertCount(3, $question->tags);

        $this->get(route('questions.show', $question))
            ->assertOk()
            ->assertSee('middleware');
    }

    /** E2 — a short title and empty body are both rejected. */
    public function test_e2_short_title_and_empty_body_are_rejected(): void
    {
        $this->actingAs($this->member())
            ->post(route('questions.store'), [
                'title'       => 'Hi',
                'body'        => '',
                'category_id' => $this->category()->id,
            ])
            ->assertSessionHasErrors(['title', 'body']);
    }

    /** E3 — the author can edit their own question. */
    public function test_e3_author_can_edit_their_question(): void
    {
        $member   = $this->member();
        $question = $this->askQuestionAs($member);

        $this->actingAs($member)->put(route('questions.update', $question), [
            'title'       => 'An edited question title that is long enough',
            'body'        => 'An edited body that comfortably passes validation.',
            'category_id' => $question->category_id,
            'tags'        => 'edited',
        ])->assertSessionHasNoErrors();

        $this->assertSame('An edited question title that is long enough', $question->fresh()->title);
    }

    /** E4 — another member can answer, and the count rises. */
    public function test_e4_another_member_can_answer(): void
    {
        $question = $this->askQuestionAs($this->member());

        $this->answerQuestionAs($this->otherMember(), $question);

        $this->assertSame(1, $question->fresh()->answers()->count());
    }

    /** E5 — an upvote raises the score by one. */
    public function test_e5_upvote_raises_the_score(): void
    {
        $question = $this->askQuestionAs($this->member());
        $before   = $question->score();

        $this->actingAs($this->otherMember())
            ->post(route('votes.question', $question), ['value' => 1]);

        $this->assertSame($before + 1, $question->fresh()->score());
    }

    /** E6 — clicking the same arrow twice withdraws the vote. */
    public function test_e6_repeating_a_vote_withdraws_it(): void
    {
        $question = $this->askQuestionAs($this->member());
        $voter    = $this->otherMember();
        $before   = $question->score();

        $this->actingAs($voter)->post(route('votes.question', $question), ['value' => 1]);
        $this->actingAs($voter)->post(route('votes.question', $question), ['value' => 1]);

        $this->assertSame($before, $question->fresh()->score());
    }

    /** E7 — switching from up to down is a net change of two. */
    public function test_e7_switching_the_vote_direction(): void
    {
        $question = $this->askQuestionAs($this->member());
        $voter    = $this->otherMember();
        $before   = $question->score();

        $this->actingAs($voter)->post(route('votes.question', $question), ['value' => 1]);
        $this->actingAs($voter)->post(route('votes.question', $question), ['value' => -1]);

        $this->assertSame($before - 1, $question->fresh()->score());
    }

    /** E8 — self-voting is refused. */
    public function test_e8_author_cannot_vote_on_their_own_question(): void
    {
        $member   = $this->member();
        $question = $this->askQuestionAs($member);
        $before   = $question->score();

        $this->actingAs($member)->post(route('votes.question', $question), ['value' => 1]);

        $this->assertSame($before, $question->fresh()->score());
    }

    /** E9 — the author can accept an answer. */
    public function test_e9_author_can_accept_an_answer(): void
    {
        $member   = $this->member();
        $question = $this->askQuestionAs($member);
        $answer   = $this->answerQuestionAs($this->otherMember(), $question);

        $this->actingAs($member)
            ->post(route('answers.accept', $answer))
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $answer->fresh()->is_accepted);
    }

    /** E10 — a non-author cannot accept an answer. */
    public function test_e10_non_author_cannot_accept_an_answer(): void
    {
        $question = $this->askQuestionAs($this->member());
        $answerer = $this->otherMember();
        $answer   = $this->answerQuestionAs($answerer, $question);

        $this->actingAs($answerer)->post(route('answers.accept', $answer))->assertForbidden();
        $this->assertFalse((bool) $answer->fresh()->is_accepted);
    }

    /** E11 — the category filter narrows the list. */
    public function test_e11_category_filter_narrows_the_list(): void
    {
        $category = $this->category();
        $question = $this->askQuestionAs($this->member(), [
            'title'       => 'A category filter probe question title',
            'category_id' => $category->id,
        ]);

        $this->get(route('questions.index', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee('A category filter probe question title');
    }

    /** E12 — keyword search finds a question by title. */
    public function test_e12_keyword_search_finds_a_question(): void
    {
        $this->askQuestionAs($this->member(), ['title' => 'Zebracrossing indexing strategy question']);

        $this->get(route('questions.index', ['q' => 'Zebracrossing']))
            ->assertOk()
            ->assertSee('Zebracrossing indexing strategy question');
    }

    /** E13 — a search with no hits shows the empty state. */
    public function test_e13_no_results_shows_the_empty_state(): void
    {
        $this->get(route('questions.index', ['q' => 'zzzqqqnothingmatches']))
            ->assertOk()
            ->assertSee('No questions match your filters');
    }

    /** E14 — the unanswered tab lists only questions with no answers. */
    public function test_e14_unanswered_tab_excludes_answered_questions(): void
    {
        $answered = $this->askQuestionAs($this->member(), ['title' => 'This one has been answered already']);
        $this->answerQuestionAs($this->otherMember(), $answered);

        $this->askQuestionAs($this->member(), ['title' => 'This one is still waiting for help']);

        $this->get(route('questions.index', ['sort' => 'unanswered']))
            ->assertOk()
            ->assertSee('This one is still waiting for help')
            ->assertDontSee('This one has been answered already');
    }

    /** E15 — opening a thread increments its view counter. */
    public function test_e15_view_counter_increments(): void
    {
        $question = $this->askQuestionAs($this->member());
        $before   = $question->fresh()->views_count;

        $this->get(route('questions.show', $question))->assertOk();

        $this->assertGreaterThan($before, $question->fresh()->views_count);
    }

    /** E16 — a closed question no longer accepts answers. */
    public function test_e16_closed_question_rejects_answers(): void
    {
        $question = $this->askQuestionAs($this->member());

        $this->actingAs($this->admin())
            ->put(route('admin.questions.toggle', $question))
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $question->fresh()->is_closed);

        $this->actingAs($this->otherMember())
            ->get(route('questions.show', $question))
            ->assertOk()
            ->assertSee('closed by a moderator');
    }

    /** E17 — an admin can re-open one question and delete another. */
    public function test_e17_admin_can_reopen_and_delete(): void
    {
        $admin    = $this->admin();
        $reopen   = $this->askQuestionAs($this->member(), ['title' => 'A question to close and reopen again']);
        $doomed   = $this->askQuestionAs($this->member(), ['title' => 'A question destined for deletion here']);

        $this->actingAs($admin)->put(route('admin.questions.toggle', $reopen));
        $this->actingAs($admin)->put(route('admin.questions.toggle', $reopen));
        $this->assertFalse((bool) $reopen->fresh()->is_closed);

        $this->actingAs($admin)->delete(route('admin.questions.destroy', $doomed));
        $this->assertDatabaseMissing('questions', ['id' => $doomed->id]);
    }
}
