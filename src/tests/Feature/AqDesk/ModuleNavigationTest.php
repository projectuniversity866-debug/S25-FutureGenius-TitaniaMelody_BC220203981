<?php

namespace Tests\Feature\AqDesk;

/**
 * Suite G — Deferred modules and navigation. Cases G1–G4.
 */
class ModuleNavigationTest extends AqDeskTestCase
{
    public static function moduleSlugs(): array
    {
        return [
            'events'     => ['events'],
            'consulting' => ['consulting'],
            'newsletter' => ['newsletter'],
        ];
    }

    /**
     * G1 — each Phase 2 placeholder renders with its planned features.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('moduleSlugs')]
    public function test_g1_phase_two_placeholders_render(string $slug): void
    {
        $this->get(route('modules.coming-soon', $slug))
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertSee('Planned features');
    }

    /** G2 — an unknown module slug is a 404, not a blank placeholder. */
    public function test_g2_unknown_module_slug_is_not_found(): void
    {
        $this->get('/modules/anything-else')->assertNotFound();
    }

    /** G3 — every public and member route responds without an error. */
    public function test_g3_all_member_routes_respond(): void
    {
        $member   = $this->member();
        $question = $this->askQuestionAs($member);

        $routes = [
            route('home'),
            route('questions.index'),
            route('questions.show', $question),
            route('questions.create'),
            route('jobs.index'),
            route('dashboard'),
            route('applications.index'),
            route('profile.show', $member),
            route('profile.edit'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($member)->get($url)->assertOk();
        }
    }

    /** G4 — the top-bar search lands on the Q&A list with the keyword applied. */
    public function test_g4_topbar_search_reaches_the_question_list(): void
    {
        $this->askQuestionAs($this->member(), ['title' => 'Topbarsearch probe question title here']);

        $this->get(route('questions.index', ['q' => 'Topbarsearch']))
            ->assertOk()
            ->assertSee('Topbarsearch probe question title here');
    }
}
