<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Services\ReputationService;
use App\Modules\Platform\Services\MarkdownRenderer;

/**
 * Suite M — tabbed profile with reputation and badges (1.6) and Markdown
 * content (1.7). Cases M1–M9.
 */
class ProfileAndMarkdownTest extends AqDeskTestCase
{
    /** M1 — reputation follows the published rules. */
    public function test_m1_reputation_rules(): void
    {
        $author  = $this->userWithRole('member');
        $expert  = $this->userWithRole('expert');
        $q       = $this->askQuestionAs($author);
        $answer  = $this->answerQuestionAs($expert, $q);

        $service = app(ReputationService::class);

        // A newcomer (1 point) may not downvote yet.
        $this->actingAs($expert)->post(route('votes.question', $q), ['value' => -1])->assertSessionHasErrors('vote');

        $this->actingAs($author)->post(route('votes.answer', $answer), ['value' => 1]);   // +10 for expert
        $this->actingAs($author)->post(route('answers.accept', $answer));               // +15 for expert
        $this->assertSame(1 + 10 + 15, $service->for($expert)['points']);

        // With 26 points the expert may downvote: −2 for the author, who stays at the floor of 1.
        $this->actingAs($expert)->post(route('votes.question', $q), ['value' => -1])->assertSessionHasNoErrors();
        $this->assertSame(1, $service->for($author)['points'], 'Reputation never drops below 1.');

        // Removing the upvote removes its 10 points from the ledger.
        $this->actingAs($author)->post(route('votes.answer', $answer), ['value' => 1]);
        $this->assertSame(1 + 15, $service->for($expert)['points']);
    }

    /** M2 — badges are earned by what you did. */
    public function test_m2_badges(): void
    {
        $expert = $this->userWithRole('expert');
        $q      = $this->askQuestionAs($this->member());
        $answer = $this->answerQuestionAs($expert, $q);
        $this->actingAs($this->member())->post(route('answers.accept', $answer));

        $earned = collect(app(ReputationService::class)->badges($expert))->where('earned', true)->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['First answer', 'Problem solver'], $earned);
    }

    /** M3 — the profile has four tabs; each shows its content. */
    public function test_m3_profile_tabs(): void
    {
        $member = $this->member();
        $q = $this->askQuestionAs($member, ['title' => 'A question that appears in the tab']);
        $this->answerQuestionAs($this->otherMember(), $q);

        $this->get(route('profile.show', $member))->assertOk()
            ->assertSee('Reputation')->assertSee('Badges')
            ->assertSeeInOrder(['Overview', 'Questions', 'Answers', 'Activity']);

        $this->get(route('profile.show', [$member, 'tab' => 'questions']))->assertOk()->assertSee('A question that appears in the tab');
        $this->get(route('profile.show', [$this->otherMember(), 'tab' => 'answers']))->assertOk()->assertSee('A question that appears in the tab');
        $this->get(route('profile.show', [$member, 'tab' => 'activity']))->assertOk()->assertSee('Asked:');
        $this->get(route('profile.show', [$member, 'tab' => 'nonsense']))->assertOk()->assertSee('Top questions');
    }

    /** M4 — Markdown renders formatting and highlights code blocks' language. */
    public function test_m4_markdown_renders(): void
    {
        $html = (string) app(MarkdownRenderer::class)->render("**Bold** and `code`\n\n```php\necho 1;\n```\n\n- one\n- two");

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
        $this->assertStringContainsString('<code class="language-php">', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
    }

    /** M5 — raw HTML and script are shown as text, never run. */
    public function test_m5_html_is_escaped(): void
    {
        $html = (string) app(MarkdownRenderer::class)->render('<script>alert(1)</script> <img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** M6 — javascript: links are removed; external links are marked. */
    public function test_m6_links_are_safe(): void
    {
        $html = (string) app(MarkdownRenderer::class)->render('[evil](javascript:alert(1)) [site](https://example.org)');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    /** M7 — questions display Markdown; lists show plain-text excerpts. */
    public function test_m7_question_page_renders_markdown_and_list_shows_plain_excerpt(): void
    {
        $q = $this->askQuestionAs($this->member(), [
            'title' => 'Markdown question rendering check',
            'body'  => 'Use **eager loading** with `with()` to avoid N+1 queries.',
        ]);

        $this->get(route('questions.show', $q))->assertSee('<strong>eager loading</strong>', false);
        $this->get(route('questions.index'))->assertSee('Use eager loading with with() to avoid')->assertDontSee('**eager loading**');
    }

    /** M8 — the preview endpoint uses the same safe renderer and needs sign-in. */
    public function test_m8_preview_endpoint(): void
    {
        $this->postJson(route('markdown.preview'), ['text' => '**hi**'])->assertUnauthorized();

        $this->actingAs($this->member())->postJson(route('markdown.preview'), ['text' => '**hi** <b>x</b>'])
            ->assertOk()
            ->assertJsonPath('html', "<p><strong>hi</strong> &lt;b&gt;x&lt;/b&gt;</p>\n");
    }

    /** M9 — the editor is offered on the ask form and the answer form. */
    public function test_m9_editor_is_mounted_on_forms(): void
    {
        $q = $this->askQuestionAs($this->expert());

        $this->actingAs($this->member())->get(route('questions.create'))->assertSee('data-vue="MarkdownEditor"', false);
        $this->actingAs($this->member())->get(route('questions.show', $q))->assertSee('data-vue="MarkdownEditor"', false);
    }
}
