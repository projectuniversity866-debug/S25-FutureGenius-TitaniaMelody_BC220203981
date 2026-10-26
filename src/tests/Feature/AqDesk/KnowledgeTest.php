<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Notifications\NewVerificationRequest;
use App\Modules\Knowledge\Notifications\VerificationDecided;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Suite X — expert verification (2.1) and the Knowledge Hub (2.2). Cases X1–X9.
 */
class KnowledgeTest extends AqDeskTestCase
{
    private function applyForVerification($user, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('verification.store'), array_merge([
            'field'       => 'Data visualisation',
            'credentials' => 'MSc in Data Science and three years building dashboards for public health teams.',
        ], $overrides));
    }

    private function articlePayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'Designing accessible charts for dashboards',
            'kind'        => 'guide',
            'category_id' => $this->category()->id,
            'summary'     => 'Colour, labels and table views that make charts readable for everyone.',
            'body'        => str_repeat('Charts need text alternatives, direct labels and enough contrast. ', 8),
            'action'      => 'publish',
        ], $overrides);
    }

    /** X1 — a member applies (with a private document); administrators are notified; one request at a time. */
    public function test_x1_apply_for_verification(): void
    {
        Storage::fake('local');
        $member = $this->userWithRole('member');

        $this->applyForVerification($member, ['document' => UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $request = ExpertVerification::where('user_id', $member->id)->firstOrFail();
        $this->assertSame('pending', $request->status);
        Storage::disk('local')->assertExists($request->document_path);
        $this->assertSame(1, $this->admin()->notifications()->where('type', NewVerificationRequest::class)->count());

        $this->applyForVerification($member)->assertSessionHasErrors(['field' => 'Your request is already waiting for review.']);
        $this->actingAs($member)->get(route('verification.show'))->assertOk()->assertSee('is reviewing your request');
    }

    /** X2 — approving shows the badge, makes a Member an Expert, notifies and logs. */
    public function test_x2_approval(): void
    {
        $member = $this->userWithRole('member');
        $this->applyForVerification($member);
        $request = ExpertVerification::where('user_id', $member->id)->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.verifications.decide', $request), ['decision' => 'approve'])
            ->assertRedirect(route('admin.verifications.index'));

        $member->refresh();
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertTrue($member->isExpert());
        $this->assertSame(1, $member->notifications()->where('type', VerificationDecided::class)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'expert.approved', 'subject_id' => $request->id]);

        $this->get(route('profile.show', $member))->assertSee('Verified expert · Data visualisation');
    }

    /** X3 — a rejection needs a reason, which the applicant sees; they may apply again. */
    public function test_x3_rejection(): void
    {
        $member = $this->userWithRole('member');
        $this->applyForVerification($member);
        $request = ExpertVerification::where('user_id', $member->id)->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.verifications.decide', $request), ['decision' => 'reject'])
            ->assertSessionHasErrors('review_note');
        $this->actingAs($this->admin())->put(route('admin.verifications.decide', $request),
            ['decision' => 'reject', 'review_note' => 'Please add a link to your published work.']);

        $this->assertTrue($member->fresh()->isMember());
        $this->actingAs($member)->get(route('verification.show'))
            ->assertSee('Please add a link to your published work.')->assertSee('Apply again');
        $this->applyForVerification($member, ['evidence_url' => 'https://portfolio.example.org/me'])->assertSessionHasNoErrors();

        // A decided request cannot be decided again.
        $this->actingAs($this->admin())->put(route('admin.verifications.decide', $request), ['decision' => 'approve'])
            ->assertSessionHasErrors('review_note');
    }

    /** X4 — only reviewers see the queue and evidence; the applicant may open their own document. */
    public function test_x4_review_access(): void
    {
        Storage::fake('local');
        $member = $this->userWithRole('member');
        $this->applyForVerification($member, ['document' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]);
        $request = ExpertVerification::where('user_id', $member->id)->firstOrFail();

        $this->actingAs($this->expert())->get(route('admin.verifications.index'))->assertForbidden();
        $this->actingAs($this->otherMember())->get(route('verification.document', $request))->assertForbidden();
        $this->actingAs($member)->get(route('verification.document', $request))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.verifications.index'))->assertOk()->assertSee($member->name);
        $this->actingAs($this->admin())->get(route('admin.verifications.show', $request))->assertOk()->assertSee('Supporting document');
    }

    /** X5 — only verified experts (or moderators) write; unverified experts are told how to qualify. */
    public function test_x5_who_may_write(): void
    {
        $this->actingAs($this->otherExpert())->get(route('articles.create'))->assertForbidden();
        $this->actingAs($this->otherExpert())->get(route('articles.index'))->assertSee('Become a verified expert');

        // The seeded expert is verified.
        $this->actingAs($this->expert())->get(route('articles.create'))->assertOk()->assertSee('data-vue="MarkdownEditor"', false);
        $this->actingAs($this->admin())->get(route('articles.create'))->assertOk();
    }

    /** X6 — publishing: slug, reading time, Markdown, listed by kind and topic, views counted. */
    public function test_x6_publish_an_article(): void
    {
        $this->actingAs($this->expert())->post(route('articles.store'), $this->articlePayload())->assertSessionHasNoErrors();
        $article = Article::where('slug', 'designing-accessible-charts-for-dashboards')->firstOrFail();

        $this->assertTrue($article->isPublished());
        $this->assertSame(1, $article->reading_minutes);
        $this->signOut();

        $this->get(route('articles.index', ['kind' => 'guide', 'category' => $this->category()->slug]))->assertOk()
            ->assertSee('Designing accessible charts for dashboards');
        $this->get(route('articles.index', ['kind' => 'article']))->assertDontSee('Designing accessible charts for dashboards');
        $this->get(route('articles.show', $article))->assertOk()->assertSee('1 min read')->assertSee('Verified expert');
        $this->assertSame(1, $article->fresh()->views_count);
    }

    /** X7 — drafts are hidden from everyone but the author and moderators. */
    public function test_x7_drafts_are_private(): void
    {
        $this->actingAs($this->expert())->post(route('articles.store'), $this->articlePayload(['title' => 'A draft that is not ready yet', 'action' => 'draft']));
        $draft = Article::where('title', 'A draft that is not ready yet')->firstOrFail();

        $this->assertNull($draft->published_at);
        $this->actingAs($this->expert())->get(route('articles.show', $draft))->assertOk()->assertSee('Draft');
        $this->actingAs($this->admin())->get(route('articles.show', $draft))->assertOk();
        $this->actingAs($this->member())->get(route('articles.show', $draft))->assertNotFound();
        $this->signOut();
        $this->get(route('articles.show', $draft))->assertNotFound();
        $this->get(route('articles.index'))->assertDontSee('A draft that is not ready yet');
    }

    /** X8 — only the author or a moderator edits or deletes; a moderator's deletion is logged. */
    public function test_x8_edit_and_delete(): void
    {
        $this->actingAs($this->expert())->post(route('articles.store'), $this->articlePayload());
        $article = Article::latest('id')->firstOrFail();

        $this->actingAs($this->otherExpert())->put(route('articles.update', $article), $this->articlePayload(['title' => 'Taken over by someone else']))
            ->assertForbidden();
        $this->actingAs($this->expert())->put(route('articles.update', $article), $this->articlePayload(['action' => 'draft']))
            ->assertSessionHasNoErrors();
        $this->assertNull($article->fresh()->published_at);

        $this->actingAs($this->admin())->delete(route('articles.destroy', $article))->assertRedirect();
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.deleted']);
    }

    /** X9 — seeded content: a verified expert with published pieces, a pending request, and a hidden draft. */
    public function test_x9_seeded_content(): void
    {
        $this->assertSame(1, ExpertVerification::where('status', 'approved')->count());
        $this->assertSame(1, ExpertVerification::where('status', 'pending')->count());
        $this->get(route('articles.index'))->assertOk()
            ->assertSee('A step-by-step guide to evaluating a classifier properly')
            ->assertDontSee('Notes on ethics approval for student research');
    }
}
