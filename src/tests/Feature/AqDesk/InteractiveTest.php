<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Models\Tag;

/**
 * Suite I — in-place interactions (1.11): votes, saving and following answer
 * JSON for the Vue islands, the plain forms keep working, and the bell can
 * refresh itself. Cases I1–I8.
 */
class InteractiveTest extends AqDeskTestCase
{
    /** I1 — a JSON vote returns the new score and your vote; voting the same way again removes it. */
    public function test_i1_vote_without_reload(): void
    {
        $q = $this->askQuestionAs($this->member());
        $voter = $this->otherMember();

        $this->actingAs($voter)->postJson(route('votes.question', $q), ['value' => 1])
            ->assertOk()->assertExactJson(['score' => 1, 'vote' => 1]);
        $this->actingAs($voter)->postJson(route('votes.question', $q), ['value' => 1])
            ->assertOk()->assertExactJson(['score' => 0, 'vote' => null]);

        $answer = $this->answerQuestionAs($this->expert(), $q);
        $this->actingAs($voter)->postJson(route('votes.answer', $answer), ['value' => 1])
            ->assertOk()->assertExactJson(['score' => 1, 'vote' => 1]);
    }

    /** I2 — a refused vote is a 422 with the reason and changes nothing; visitors get a 401. */
    public function test_i2_refused_votes_explain_why(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author);

        $this->actingAs($author)->postJson(route('votes.question', $q), ['value' => 1])
            ->assertStatus(422)->assertJsonPath('errors.vote.0', 'You cannot vote on your own post.');

        $this->actingAs($this->userWithRole('member'))->postJson(route('votes.question', $q), ['value' => -1])
            ->assertStatus(422)
            ->assertJsonPath('errors.vote.0', 'You need 15 reputation to downvote. Upvoting is open to everyone.');

        $this->actingAs($this->otherMember())->postJson(route('votes.question', $q), ['value' => 5])
            ->assertStatus(422)->assertJsonValidationErrors('value');

        $this->assertSame(0, $q->votes()->count());

        $this->signOut();
        $this->postJson(route('votes.question', $q), ['value' => 1])->assertUnauthorized();
    }

    /** I3 — without JavaScript the plain vote form still works and returns to the answer. */
    public function test_i3_plain_vote_form_returns_to_the_answer(): void
    {
        $q = $this->askQuestionAs($this->member());
        $answer = $this->answerQuestionAs($this->expert(), $q);

        $this->actingAs($this->otherMember())->post(route('votes.answer', $answer), ['value' => 1])
            ->assertRedirect(route('questions.show', $q).'#answer-'.$answer->id);
        $this->assertSame(1, $answer->score());
    }

    /** I4 — saving and following answer JSON with the new state, count and message. */
    public function test_i4_save_and_follow_as_json(): void
    {
        $q = $this->askQuestionAs($this->expert(), ['tags' => 'svelte']);
        $reader = $this->member();

        $this->actingAs($reader)->postJson(route('bookmarks.store', $q))->assertOk()
            ->assertJson(['saved' => true, 'count' => 1, 'message' => 'Question saved. You can find it under Saved.']);
        $this->actingAs($reader)->deleteJson(route('bookmarks.destroy', $q))->assertOk()
            ->assertJson(['saved' => false, 'count' => 0]);

        $tag = Tag::where('slug', 'svelte')->firstOrFail();
        $this->actingAs($reader)->postJson(route('tags.follow', $tag))->assertOk()
            ->assertJson(['following' => true, 'count' => 1])
            ->assertJsonPath('message', 'You now follow svelte. New questions with this tag will appear in your notifications.');
        $this->actingAs($reader)->deleteJson(route('tags.unfollow', $tag))->assertOk()
            ->assertJson(['following' => false, 'count' => 0, 'message' => 'You no longer follow svelte.']);
    }

    /** I5 — the page mounts the Vue buttons around the plain forms, with the current state as props. */
    public function test_i5_islands_wrap_the_plain_forms(): void
    {
        $q = $this->askQuestionAs($this->expert(), ['tags' => 'deno']);
        $html = $this->actingAs($this->member())->get(route('questions.show', $q))->assertOk()->getContent();

        foreach (['VoteControl', 'BookmarkButton', 'FollowButton'] as $island) {
            $this->assertStringContainsString('data-vue="'.$island.'"', $html);
        }

        // The no-JavaScript form sits inside the island's mount point.
        $this->assertMatchesRegularExpression(
            '#data-vue="VoteControl"[^>]*>\s*<form method="POST" action="'.preg_quote(route('votes.question', $q), '#').'"#', $html);

        preg_match('#data-vue="VoteControl" data-props="([^"]+)"#', $html, $match);
        $this->assertSame(
            ['url' => route('votes.question', $q), 'score' => 0, 'vote' => null, 'noun' => 'question'],
            json_decode(html_entity_decode($match[1]), true));

        // Visitors see the score, not buttons.
        $this->signOut();
        $this->get(route('questions.show', $q))->assertOk()
            ->assertDontSee('data-vue="VoteControl"', false)
            ->assertSee('<div class="aq-vote-score">0', false);
    }

    /** I6 — the bell summary: unread count, label, newest notification and the dropdown HTML; your own only. */
    public function test_i6_bell_summary(): void
    {
        $author = $this->member();
        $q = $this->askQuestionAs($author, ['title' => 'Question for the live bell test here']);
        $author->notifications()->delete();

        $this->actingAs($author)->getJson(route('notifications.summary'))->assertOk()
            ->assertExactJson([
                'unread' => 0, 'label' => 'Notifications', 'newest' => null,
                'menu' => view('platform::partials.bell-menu', ['unread' => 0])->render(),
            ]);

        $this->answerQuestionAs($this->otherMember(), $q);

        $summary = $this->actingAs($author)->getJson(route('notifications.summary'))->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('label', 'Notifications, 1 unread')
            ->assertJsonPath('newest.title', 'New answer to your question');
        $this->assertStringContainsString('Question for the live bell test here', $summary->json('menu'));
        $this->assertStringContainsString('data-aq-bell-read-all', $summary->json('menu'));

        // The page carries what the script needs.
        $this->actingAs($author)->get(route('dashboard'))
            ->assertSee('data-url="'.route('notifications.summary').'"', false)
            ->assertSee('data-unread="1"', false);

        $this->signOut();
        $this->getJson(route('notifications.summary'))->assertUnauthorized();
    }

    /** I7 — "Mark all read" from the dropdown answers with a fresh summary. */
    public function test_i7_mark_all_read_as_json(): void
    {
        $author = $this->member();
        $this->answerQuestionAs($this->otherMember(), $this->askQuestionAs($author));

        $this->actingAs($author)->postJson(route('notifications.read-all'))->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonPath('label', 'Notifications')
            ->assertJsonPath('newest', null);
        $this->assertSame(0, $author->unreadNotifications()->count());
    }

    /** I8 — notification links take ids only, so /notifications/summary is never read as one. */
    public function test_i8_notification_routes(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get('/notifications/not-a-real-id')->assertNotFound();
        $this->actingAs($member)->get('/notifications/summary')->assertOk()->assertJsonStructure(['unread', 'label', 'newest', 'menu']);
    }
}
