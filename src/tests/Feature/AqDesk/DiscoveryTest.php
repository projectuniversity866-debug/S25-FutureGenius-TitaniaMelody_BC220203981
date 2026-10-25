<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Models\Bookmark;
use App\Modules\Community\Models\Category;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Tag;
use App\Modules\Community\Models\Vote;
use App\Modules\Community\Notifications\NewQuestionInFollowedTag;
use App\Modules\Community\Services\QuestionFeed;
use App\Modules\Community\Services\ReputationService;
use App\Modules\Community\Services\TagFollowService;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Suite D — discovery (1.10): sort orders and trending, following tags,
 * saved questions, related questions, the tag directory, and clean-up when
 * a post is deleted. Cases D1–D10.
 */
class DiscoveryTest extends AqDeskTestCase
{
    private function follow(User $user, string $tag): Tag
    {
        $tag = Tag::findOrCreateByName($tag);
        app(TagFollowService::class)->follow($user, $tag);

        return $tag;
    }

    /** D1 — "Most votes" ranks by score (negative last); Newest is the default, also for unknown sorts. */
    public function test_d1_most_votes_and_default_sort(): void
    {
        $high = $this->askQuestionAs($this->member(), ['title' => 'Votes tab probe: three upvotes']);
        $negative = $this->askQuestionAs($this->member(), ['title' => 'Votes tab probe: one downvote']);
        $zero = $this->askQuestionAs($this->member(), ['title' => 'Votes tab probe: no votes']);

        foreach ([$this->otherMember(), $this->expert(), $this->admin()] as $voter) {
            $this->actingAs($voter)->post(route('votes.question', $high), ['value' => 1]);
        }
        $this->actingAs($this->admin())->post(route('votes.question', $negative), ['value' => -1]);
        $this->signOut();

        $this->get(route('questions.index', ['sort' => 'votes', 'q' => 'Votes tab probe']))->assertOk()
            ->assertSeeInOrder(['three upvotes', 'no votes', 'one downvote']);

        foreach ([[], ['sort' => 'popular'], ['sort' => ['x']]] as $params) {
            $this->get(route('questions.index', $params + ['q' => 'Votes tab probe']))->assertOk()
                ->assertSeeInOrder(['no votes', 'one downvote', 'three upvotes']);
        }

        $this->get(route('questions.index'))->assertSeeInOrder(['Newest', 'Trending', 'Most votes', 'Unanswered']);
    }

    /** D2 — Trending ranks the last 7 days of answers, upvotes and comments; older activity does not count. */
    public function test_d2_trending_counts_recent_activity_only(): void
    {
        $busy = $this->askQuestionAs($this->member(), ['title' => 'Trending probe: busy this week']);
        $comment = $this->askQuestionAs($this->member(), ['title' => 'Trending probe: one comment']);
        $old = $this->askQuestionAs($this->member(), ['title' => 'Trending probe: busy last month']);

        $this->answerQuestionAs($this->otherMember(), $busy);
        $this->answerQuestionAs($this->expert(), $busy);
        $this->actingAs($this->otherMember())->post(route('votes.question', $busy), ['value' => 1]);

        $this->actingAs($this->member())->post(route('comments.question', $comment), ['body' => 'Some extra context here.']);

        $this->answerQuestionAs($this->expert(), $old)->forceFill(['created_at' => now()->subDays(10)])->save();
        $this->actingAs($this->otherMember())->post(route('votes.question', $old), ['value' => 1]);
        Vote::where('votable_type', 'question')->where('votable_id', $old->id)->update(['created_at' => now()->subDays(9)]);
        $this->signOut();

        $this->get(route('questions.index', ['sort' => 'trending', 'q' => 'Trending probe']))->assertOk()
            ->assertSee('Most new answers, votes and comments in the last 7 days.')
            ->assertSeeInOrder(['busy this week', 'one comment'])
            ->assertDontSee('busy last month');

        $this->get(route('questions.index', ['sort' => 'trending', 'q' => 'zzz-nothing']))
            ->assertSee('No questions match your filters');
    }

    /** D3 — following a tag: sign-in needed, twice is harmless, the tag header shows it, unfollow works. */
    public function test_d3_follow_and_unfollow_a_tag(): void
    {
        $this->askQuestionAs($this->member(), ['tags' => 'kubernetes']);
        $tag = Tag::where('slug', 'kubernetes')->firstOrFail();
        $this->signOut();

        $this->post(route('tags.follow', $tag))->assertRedirect(route('login'));
        $this->get(route('questions.index', ['tag' => 'kubernetes']))->assertSee('Sign in to follow');

        $fan = $this->otherMember();
        $this->actingAs($fan)->post(route('tags.follow', $tag))->assertRedirect()->assertSessionHas('status');
        $this->actingAs($fan)->post(route('tags.follow', $tag));
        $this->assertSame(1, $tag->followers()->count());

        $this->actingAs($fan)->get(route('questions.index', ['tag' => 'kubernetes']))->assertOk()
            ->assertSee('1 follower')
            ->assertSee('Following kubernetes, select to unfollow');

        $this->actingAs($fan)->delete(route('tags.unfollow', $tag))->assertRedirect();
        $this->assertSame(0, $tag->followers()->count());
    }

    /** D4 — a new question notifies each follower of its tags once — never the author or a deactivated account. */
    public function test_d4_followers_hear_about_new_questions_once(): void
    {
        $author = $this->member();
        $fan = $this->otherMember();
        $inactive = $this->userWithRole('member');

        foreach ([$fan, $author, $inactive] as $user) {
            $this->follow($user, 'graphql');
        }
        $this->follow($fan, 'apollo');
        $inactive->update(['is_active' => false]);

        $question = $this->askQuestionAs($author, ['title' => 'How do I paginate a GraphQL connection', 'tags' => 'graphql, apollo']);

        $notes = $fan->notifications()->where('type', NewQuestionInFollowedTag::class)->get();
        $this->assertCount(1, $notes);
        $this->assertStringContainsString('graphql', $notes->first()->data['title']);
        $this->assertStringContainsString('apollo', $notes->first()->data['title']);
        $this->assertSame(route('questions.show', $question), $notes->first()->data['url']);

        $this->assertSame(0, $author->notifications()->where('type', NewQuestionInFollowedTag::class)->count());
        $this->assertSame(0, $inactive->notifications()->where('type', NewQuestionInFollowedTag::class)->count());
    }

    /** D5 — tag emails are opt-in under Account settings; in-app is always on. */
    public function test_d5_tag_emails_are_opt_in(): void
    {
        Notification::fake();
        $fan = $this->otherMember();
        $this->follow($fan, 'rust');

        $this->askQuestionAs($this->member(), ['title' => 'First question about Rust lifetimes here', 'tags' => 'rust']);
        Notification::assertSentTo($fan, NewQuestionInFollowedTag::class, fn ($n, $channels) => $channels === ['database']);

        $this->actingAs($fan)->get(route('settings.show'))->assertSee('New questions in tags I follow');
        $this->actingAs($fan)->put(route('settings.update'), ['profile_visibility' => 'public', 'email_follows' => '1']);

        $this->askQuestionAs($this->member(), ['title' => 'Second question about Rust borrowing rules', 'tags' => 'rust']);
        Notification::assertSentTo($fan, NewQuestionInFollowedTag::class, fn ($n, $channels) => $channels === ['database', 'mail']);
    }

    /** D6 — "Only tags I follow" narrows the list; the chip says so. */
    public function test_d6_filter_to_followed_tags(): void
    {
        $this->askQuestionAs($this->member(), ['title' => 'Docker volumes lose data on restart', 'tags' => 'docker']);
        $this->askQuestionAs($this->member(), ['title' => 'Spreadsheet pivot tables refresh slowly', 'tags' => 'spreadsheets']);
        $fan = $this->otherMember();
        $this->follow($fan, 'docker');

        $this->actingAs($fan)->get(route('questions.index', ['following' => 1]))->assertOk()
            ->assertSee('Docker volumes lose data on restart')
            ->assertDontSee('Spreadsheet pivot tables refresh slowly')
            ->assertSee('Showing: <strong>tags I follow</strong>', false)
            ->assertSee('Only tags I follow (1)');

        // Visitors cannot follow, so the parameter changes nothing for them.
        $this->signOut();
        $this->get(route('questions.index', ['following' => 1]))->assertOk()->assertSee('Spreadsheet pivot tables refresh slowly');
    }

    /** D7 — saving questions: newest saved first, twice is harmless, private, removable, sign-in needed. */
    public function test_d7_save_questions_for_later(): void
    {
        $first = $this->askQuestionAs($this->expert(), ['title' => 'Saved first: queue workers on shared hosting']);
        $second = $this->askQuestionAs($this->expert(), ['title' => 'Saved second: cron jobs on shared hosting']);
        $reader = $this->member();

        $this->actingAs($reader)->post(route('bookmarks.store', $first))->assertSessionHas('status');
        $this->travel(1)->minutes();
        $this->actingAs($reader)->post(route('bookmarks.store', $second));
        $this->actingAs($reader)->post(route('bookmarks.store', $second));
        $this->assertSame(1, $second->bookmarks()->count());

        $this->actingAs($reader)->get(route('saved.index'))->assertOk()
            ->assertSeeInOrder(['Saved second: cron jobs', 'Saved first: queue workers'])
            ->assertSee('Remove');
        $this->actingAs($reader)->get(route('questions.show', $first))->assertSee('aria-pressed="true"', false);

        $this->actingAs($this->otherMember())->get(route('saved.index'))->assertOk()->assertSee('Nothing saved yet');

        $this->actingAs($reader)->delete(route('bookmarks.destroy', $first))->assertRedirect();
        $this->actingAs($reader)->get(route('saved.index'))->assertDontSee('Saved first: queue workers');

        $this->signOut();
        $this->get(route('saved.index'))->assertRedirect(route('login'));
        $this->post(route('bookmarks.store', $first))->assertRedirect(route('login'));
    }

    /** D8 — deleting a question removes its votes, comments and bookmarks, and the ledger stays consistent. */
    public function test_d8_deleting_a_post_leaves_nothing_behind(): void
    {
        $author = $this->member();
        $expert = $this->expert();
        $reputation = app(ReputationService::class);
        $start = $reputation->points($expert);

        $q = $this->askQuestionAs($author, ['title' => 'A question that is deleted with everything on it']);
        $answer = $this->answerQuestionAs($expert, $q);
        $this->actingAs($this->otherMember())->post(route('votes.question', $q), ['value' => 1]);
        $this->actingAs($author)->post(route('votes.answer', $answer), ['value' => 1]);
        $this->actingAs($author)->post(route('answers.accept', $answer));
        $this->actingAs($author)->post(route('comments.question', $q), ['body' => 'A comment that must go too.']);
        $this->actingAs($this->otherMember())->post(route('bookmarks.store', $q));
        $this->assertSame($start + 10 + 15, $reputation->points($expert));

        $this->actingAs($author)->delete(route('questions.destroy', $q))->assertRedirect();

        $this->assertSame(0, Vote::where('votable_type', 'question')->where('votable_id', $q->id)->count());
        $this->assertSame(0, Vote::where('votable_type', 'answer')->where('votable_id', $answer->id)->count());
        $this->assertSame(0, Comment::where('commentable_type', 'question')->where('commentable_id', $q->id)->count());
        $this->assertSame(0, Bookmark::where('bookmarkable_id', $q->id)->count());
        $this->assertSame($start, $reputation->points($expert));

        $live = User::all()->mapWithKeys(fn ($u) => [$u->id => $reputation->points($u)])->all();
        $this->artisan('community:rebuild-reputation')->assertSuccessful();
        $this->assertEquals($live, User::all()->mapWithKeys(fn ($u) => [$u->id => $reputation->points($u)])->all());
    }

    /** D9 — related questions: most shared tags first, never itself; other categories are not padding. */
    public function test_d9_related_questions(): void
    {
        $otherCategory = Category::where('id', '!=', $this->category()->id)->firstOrFail();

        $base = $this->askQuestionAs($this->member(), ['title' => 'Base question about queues and redis', 'tags' => 'queues, redis, horizon']);
        $close = $this->askQuestionAs($this->expert(), ['title' => 'Close match sharing two tags', 'tags' => 'queues, redis']);
        $loose = $this->askQuestionAs($this->expert(), ['title' => 'Loose match sharing one tag', 'tags' => 'horizon']);
        $other = $this->askQuestionAs($this->expert(), ['title' => 'Unrelated photo editing question',
            'tags' => 'photoshop', 'category_id' => $otherCategory->id]);
        $this->signOut();

        $this->get(route('questions.show', $base))->assertOk()
            ->assertSeeInOrder(['Related questions', 'Close match sharing two tags', 'Loose match sharing one tag'])
            ->assertSee('Follow these tags');

        $related = app(QuestionFeed::class)->related($base->fresh('tags'));
        $this->assertSame([$close->id, $loose->id], array_slice($related->modelKeys(), 0, 2));
        $this->assertNotContains($base->id, $related->modelKeys());
        $this->assertNotContains($other->id, $related->modelKeys());
        $this->assertLessThanOrEqual(5, $related->count());
    }

    /** D10 — the tag directory: counts, search, follow state; odd input does not break pages. */
    public function test_d10_tag_directory(): void
    {
        $this->askQuestionAs($this->member(), ['tags' => 'zigzag-lang']);
        $fan = $this->otherMember();
        $this->follow($fan, 'zigzag-lang');
        $this->signOut();

        $this->get(route('tags.index', ['q' => 'zigzag']))->assertOk()
            ->assertSee('zigzag-lang')->assertSee('1 follower')->assertSee('1 tag matching')->assertSee('1 this week');

        $this->actingAs($fan)->get(route('tags.index', ['q' => 'zigzag']))
            ->assertSee('Following zigzag-lang, select to unfollow');

        foreach ([['sort' => 'name'], ['sort' => 'new'], ['sort' => ['x']], ['q' => ['x']]] as $params) {
            $this->get(route('tags.index', $params))->assertOk();
        }
        $this->get(route('questions.index', ['q' => ['x'], 'tag' => ['y']]))->assertOk();
    }
}
