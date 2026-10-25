<?php

namespace Tests\Feature\AqDesk;

/**
 * Suite C — Dashboard (Module 3). Cases C1–C5.
 */
class DashboardTest extends AqDeskTestCase
{
    /** C1 — a member sees their role badge and no admin controls. */
    public function test_c1_member_dashboard_has_no_admin_controls(): void
    {
        $this->actingAs($this->member())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertDontSee(route('admin.users.index'));
    }

    /** C2 — an expert is offered the post-an-opportunity action. */
    public function test_c2_expert_dashboard_offers_posting(): void
    {
        $this->actingAs($this->expert())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('jobs.create'));
    }

    /** C3 — an admin is offered the admin panel. */
    public function test_c3_admin_dashboard_offers_the_admin_panel(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.index'));
    }

    /** C4 — a brand-new account gets empty states, not blank panels. */
    public function test_c4_new_account_sees_empty_states(): void
    {
        $fresh = $this->userWithRole('member');

        $this->actingAs($fresh)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You have not asked anything yet')
            ->assertSee('No applications yet');
    }

    /** C5 — asking a question makes it appear under My questions. */
    public function test_c5_new_question_appears_on_the_dashboard(): void
    {
        $member   = $this->userWithRole('member');
        $question = $this->askQuestionAs($member, ['title' => 'A distinctive dashboard question title']);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('A distinctive dashboard question title');
    }
}
