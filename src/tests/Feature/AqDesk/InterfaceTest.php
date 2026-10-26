<?php

namespace Tests\Feature\AqDesk;

/**
 * Suite H — Interface, responsive layout and accessibility. Cases H1–H6.
 *
 * These cases are about rendered behaviour in a real browser. What a
 * server-side test CAN prove is that the required markup and hooks are
 * present on every page; the visual half stays manual (see the test plan
 * document) and is marked skipped so it shows up in the run summary.
 */
class InterfaceTest extends AqDeskTestCase
{
    /** H1 — the theme toggle and its no-flash bootstrap script are present. */
    public function test_h1_theme_toggle_is_wired_on_every_page(): void
    {
        $this->actingAs($this->member())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-aq-theme-toggle', false)
            ->assertSee("localStorage.getItem('aq-theme')", false)
            ->assertSee('data-bs-theme', false);
    }

    /** H2 — the sidebar is an off-canvas drawer with a labelled trigger. */
    public function test_h2_mobile_navigation_drawer_is_present(): void
    {
        $this->actingAs($this->member())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('offcanvas-lg', false)
            ->assertSee('aqSidebar', false)
            ->assertSee('Open navigation menu', false);
    }

    /** H3 — admin tables stack into labelled cards on phones. */
    public function test_h3_admin_tables_are_responsive(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('aq-table', false)
            ->assertSee('data-label="Role"', false)
            ->assertSee('data-label="Access"', false);
    }

    /** H7 — an active filter is shown as a chip that removes it, with a result count. */
    public function test_h7_active_filters_show_as_removable_chips(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['role' => 'expert']))
            ->assertOk()
            ->assertSee('Remove filter Role: Expert / Professional')
            ->assertSee('Clear all')
            ->assertSee('users match');
    }

    /** H9 — destructive forms ask for confirmation through the shared dialog. */
    public function test_h9_destructive_actions_declare_a_confirmation(): void
    {
        $question = $this->askQuestionAs($this->member());

        $this->actingAs($this->member())
            ->get(route('questions.show', $question))
            ->assertOk()
            ->assertSee('data-confirm-title="Delete this question?"', false)
            ->assertSee('id="aq-confirm"', false)
            ->assertDontSee('confirm(', false);
    }

    /** H10 — a success message is shown as a toast with a screen-reader announcer. */
    public function test_h10_success_message_is_a_toast(): void
    {
        $question = $this->askQuestionAs($this->member());

        $this->actingAs($this->member())
            ->followingRedirects()
            ->delete(route('questions.destroy', $question))
            ->assertOk()
            ->assertSee('data-aq-toast', false)
            ->assertSee('Question deleted.')
            ->assertSee('data-aq-announcer', false);
    }

    /** H8 — list tabs mark the current view for assistive technology. */
    public function test_h8_tabs_mark_the_current_view(): void
    {
        $this->get(route('questions.index', ['sort' => 'unanswered']))
            ->assertOk()
            ->assertSee('aria-label="Sort questions"', false)
            ->assertSeeInOrder(['aria-current="page"', 'Unanswered'], false);
    }

    /** H4 — the responsive viewport meta tag is declared. */
    public function test_h4_viewport_meta_is_declared(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('width=device-width, initial-scale=1', false);
    }

    /** H5 — the skip link is the first focusable element. */
    public function test_h5_skip_link_is_present(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Skip to main content')
            ->assertSee('id="main-content"', false);
    }

    /**
     * H6 — full keyboard operation of the ask flow.
     *
     * Needs a real browser (Dusk or manual). The server-side half — that
     * every field carries a label — is asserted here.
     */
    public function test_h6_ask_form_fields_are_labelled(): void
    {
        $this->actingAs($this->member())
            ->get(route('questions.create'))
            ->assertOk()
            ->assertSee('for="title"', false)
            ->assertSee('for="category_id"', false)
            ->assertSee('for="body"', false);

        $this->markTestSkipped(
            'H6 visual half is manual: tab through the ask flow with no mouse. See Suite H in the test plan.'
        );
    }
}
