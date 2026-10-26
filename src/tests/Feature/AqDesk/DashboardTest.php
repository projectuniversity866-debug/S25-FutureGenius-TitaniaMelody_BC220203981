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

    /** C6 — the admin dashboard charts activity and topics, with a table view. */
    public function test_c6_admin_dashboard_has_charts_with_table_view(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Community activity')
            ->assertSee('data-vue="LineChart"', false)
            ->assertSee('View as table')
            ->assertSee('Questions by topic')
            ->assertSee('Internet Governance')
            ->assertSee('Applications by status')
            ->assertSee('Needs an answer');
    }

    /** C7 — today's question is counted in today's slot of the activity series. */
    public function test_c7_activity_series_counts_todays_posts(): void
    {
        $metrics = app(\App\Services\DashboardMetrics::class);
        $before  = $metrics->admin()['activity']['series'][0]['values'];

        $this->askQuestionAs($this->member());

        $after = $metrics->admin()['activity']['series'][0]['values'];

        $this->assertCount(\App\Services\DashboardMetrics::DAYS, $after);
        $this->assertSame(end($before) + 1, end($after));
        $this->assertSame(array_sum($before) + 1, array_sum($after));
    }

    /** C8 — an expert sees their acceptance rate; a member sees votes received. */
    public function test_c8_role_specific_figures(): void
    {
        $member   = $this->userWithRole('member');
        $expert   = $this->userWithRole('expert');
        $question = $this->askQuestionAs($member);
        $answer   = $this->answerQuestionAs($expert, $question);

        $this->actingAs($member)->post(route('answers.accept', $answer));
        $this->actingAs($expert)->post(route('votes.question', $question), ['value' => 1]);

        $this->actingAs($expert)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Accepted answers')
            ->assertSee('100% of your answers')
            ->assertSee('Hiring pipeline');

        $tiles = collect(app(\App\Services\DashboardMetrics::class)->member($member)['tiles'])->keyBy('label');
        $this->assertSame(1, $tiles['Votes received']['value']);
        $this->assertSame(1, $tiles['Questions asked']['value']);
    }
}
