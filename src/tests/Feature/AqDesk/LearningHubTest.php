<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningResource;

/**
 * Suite L — Learning Hub (2.3): library, learning paths, progress. Cases L1–L7.
 */
class LearningHubTest extends AqDeskTestCase
{
    private function resourcePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Intro to Laravel queues', 'kind' => 'video', 'level' => 'beginner',
            'url' => 'https://videos.example.org/laravel-queues', 'provider' => 'Example Academy',
            'description' => 'A twenty-minute walk through jobs, workers and failed jobs.', 'minutes' => 20,
            'category_id' => $this->category()->id, 'is_free' => '1',
        ], $overrides);
    }

    /** L1 — the seeded hub lists published paths and the library; filters narrow it. */
    public function test_l1_hub_and_library(): void
    {
        $this->get(route('learning.index'))->assertOk()
            ->assertSee('Start with web accessibility')->assertSee('Your first machine learning project');

        $this->get(route('learning.resources', ['kind' => 'course', 'level' => 'beginner']))->assertOk()
            ->assertSee('Machine Learning Crash Course')->assertDontSee('MDN — ARIA basics');
        $this->get(route('learning.resources', ['q' => 'NVDA']))->assertSee('Testing with a screen reader');
        $this->get(route('learning.resources', ['kind' => ['x'], 'q' => ['y']]))->assertOk();
    }

    /** L2 — only verified experts or moderators curate; unverified experts are refused. */
    public function test_l2_who_curates(): void
    {
        $this->actingAs($this->otherExpert())->get(route('learning.resources.create'))->assertForbidden();
        $this->actingAs($this->member())->post(route('learning.resources.store'), $this->resourcePayload())->assertForbidden();

        $this->actingAs($this->expert())->post(route('learning.resources.store'), $this->resourcePayload())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('learning_resources', ['title' => 'Intro to Laravel queues', 'is_free' => true]);

        $this->actingAs($this->expert())->post(route('learning.resources.store'), $this->resourcePayload(['url' => 'javascript:alert(1)', 'kind' => 'podcast']))
            ->assertSessionHasErrors(['url', 'kind']);
    }

    /** L3 — a path is built step by step: add, reorder, remove; it cannot be published empty. */
    public function test_l3_build_a_path(): void
    {
        $expert = $this->expert();
        $this->actingAs($expert)->post(route('learning.paths.store'), [
            'title' => 'Queues from zero to production', 'summary' => 'Everything you need to run background jobs reliably.', 'level' => 'intermediate',
        ])->assertSessionHasNoErrors();
        $path = LearningPath::where('title', 'Queues from zero to production')->firstOrFail();

        $this->actingAs($expert)->put(route('learning.paths.update', $path), ['title' => $path->title, 'summary' => $path->summary, 'level' => 'intermediate', 'action' => 'publish'])
            ->assertSessionHasErrors('publish');

        [$a, $b] = LearningResource::orderBy('id')->take(2)->get()->all();
        $this->actingAs($expert)->post(route('learning.steps.store', $path), ['learning_resource_id' => $a->id, 'note' => 'Start here']);
        $this->actingAs($expert)->post(route('learning.steps.store', $path), ['learning_resource_id' => $b->id]);
        $this->actingAs($expert)->post(route('learning.steps.store', $path), ['learning_resource_id' => $a->id])
            ->assertSessionHasErrors('learning_resource_id');

        $second = $path->steps()->where('learning_resource_id', $b->id)->firstOrFail();
        $this->actingAs($expert)->put(route('learning.steps.move', $second), ['direction' => 'up']);
        $this->assertSame([$b->id, $a->id], $path->steps()->pluck('learning_resource_id')->map('intval')->all());

        $this->actingAs($expert)->delete(route('learning.steps.destroy', $second));
        $this->assertSame([[$a->id, 1]], $path->steps()->get()->map(fn ($s) => [(int) $s->learning_resource_id, (int) $s->position])->all());

        $this->actingAs($expert)->put(route('learning.paths.update', $path), ['title' => $path->title, 'summary' => $path->summary, 'level' => 'intermediate', 'action' => 'publish'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($path->fresh()->isPublished());
    }

    /** L4 — draft paths are hidden from learners and visitors. */
    public function test_l4_drafts_are_private(): void
    {
        $this->actingAs($this->expert())->post(route('learning.paths.store'), [
            'title' => 'A draft path nobody should see', 'summary' => 'Still being written by its curator.', 'level' => 'beginner',
        ]);
        $path = LearningPath::where('title', 'A draft path nobody should see')->firstOrFail();

        $this->actingAs($this->member())->get(route('learning.paths.show', $path))->assertNotFound();
        $this->actingAs($this->expert())->get(route('learning.paths.show', $path))->assertOk()->assertSee('Draft');
        $this->signOut();
        $this->get(route('learning.index'))->assertDontSee('A draft path nobody should see');
    }

    /** L5 — learners tick steps on and off; progress shows on the path, the hub and My learning. */
    public function test_l5_progress(): void
    {
        $member = $this->otherMember();
        $path = LearningPath::where('slug', 'your-first-machine-learning-project')->firstOrFail();
        $step = $path->steps()->firstOrFail();

        $this->actingAs($member)->post(route('learning.steps.toggle', $step))->assertRedirect();
        $this->actingAs($member)->get(route('learning.paths.show', $path))->assertSee('1 of 3')->assertSee('aria-pressed="true"', false);
        $this->actingAs($member)->get(route('learning.mine'))->assertSee('Your first machine learning project')->assertSee('1 of 3 steps');
        $this->actingAs($member)->get(route('learning.index'))->assertSee('1/3 done');

        $this->actingAs($member)->post(route('learning.steps.toggle', $step));
        $this->actingAs($member)->get(route('learning.paths.show', $path))->assertSee('0 of 3');

        $this->signOut();
        $this->post(route('learning.steps.toggle', $step))->assertRedirect(route('login'));
    }

    /** L6 — finishing every step says so; seeded progress exists for the member. */
    public function test_l6_completion(): void
    {
        $member = $this->member(); // seeded: 2 of 4 steps done
        $path = LearningPath::where('slug', 'start-with-web-accessibility')->firstOrFail();
        $this->actingAs($member)->get(route('learning.paths.show', $path))->assertSee('2 of 4');

        foreach ($path->steps()->skip(2)->take(2)->get() as $step) {
            $this->actingAs($member)->post(route('learning.steps.toggle', $step));
        }
        $this->actingAs($member)->get(route('learning.paths.show', $path))->assertSee('Path complete');
    }

    /** L7 — only the curator or a moderator edits; deleting a resource removes it from paths. */
    public function test_l7_edit_rights(): void
    {
        $resource = LearningResource::where('title', 'WCAG 2.1 at a glance')->firstOrFail();
        $this->actingAs($this->otherExpert())->put(route('learning.resources.update', $resource), $this->resourcePayload())->assertForbidden();

        $this->actingAs($this->admin())->delete(route('learning.resources.destroy', $resource))->assertRedirect();
        $path = LearningPath::where('slug', 'start-with-web-accessibility')->firstOrFail();
        $this->assertSame(3, $path->steps()->count());
    }
}
