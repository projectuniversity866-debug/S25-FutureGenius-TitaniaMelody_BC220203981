<?php

namespace Tests\Feature\AqDesk;

/**
 * Suite B — Role-based access control (Module 2). Cases B1–B14.
 *
 * This is the suite that proves the access model: the same URL must behave
 * differently per role, and guessing a URL must not bypass anything.
 */
class RoleAccessTest extends AqDeskTestCase
{
    /** B1 — the dashboard requires authentication. */
    public function test_b1_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** B2 — public browsing works, but the answer form is not offered. */
    public function test_b2_guest_can_browse_but_not_participate(): void
    {
        $question = $this->askQuestionAs($this->member());
        $this->signOut();

        $this->get(route('questions.index'))->assertOk();
        $this->get(route('jobs.index'))->assertOk();

        $this->get(route('questions.show', $question))
            ->assertOk()
            ->assertSee(route('login'));
    }

    /** B3 — a member sees no administration links in the sidebar. */
    public function test_b3_member_sidebar_hides_administration(): void
    {
        $this->actingAs($this->member())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'))
            ->assertDontSee(route('jobs.mine'));
    }

    /** B4 — a member guessing the admin URL is forbidden. */
    public function test_b4_member_cannot_reach_the_admin_area(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.questions.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.jobs.index'))->assertForbidden();
    }

    /** B5 — a member cannot open the job creation form. */
    public function test_b5_member_cannot_post_an_opportunity(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get(route('jobs.create'))->assertForbidden();
        $this->actingAs($member)->post(route('jobs.store'), $this->jobPayload())->assertForbidden();
    }

    /** B6 — a member cannot edit or delete another member's question. */
    public function test_b6_member_cannot_edit_another_members_question(): void
    {
        $question = $this->askQuestionAs($this->member());
        $intruder = $this->otherMember();

        $this->actingAs($intruder)->get(route('questions.edit', $question))->assertForbidden();
        $this->actingAs($intruder)->delete(route('questions.destroy', $question))->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    /** B7 — the same URL that 403s for a member loads for an expert. */
    public function test_b7_expert_can_open_the_job_form(): void
    {
        $this->actingAs($this->expert())->get(route('jobs.create'))->assertOk();
    }

    /** B8 — moderation is administrator-only, so an expert is forbidden. */
    public function test_b8_expert_cannot_moderate(): void
    {
        $expert = $this->expert();

        $this->actingAs($expert)->get(route('admin.questions.index'))->assertForbidden();
        $this->actingAs($expert)->get(route('admin.users.index'))->assertForbidden();
    }

    /** B9 — an expert only sees applicants for postings they own. */
    public function test_b9_expert_cannot_see_another_experts_applicants(): void
    {
        $job = $this->postJobAs($this->expert());

        $this->actingAs($this->otherExpert())
            ->get(route('jobs.applicants', $job))
            ->assertForbidden();
    }

    /** B10 — an admin reaches every administration screen. */
    public function test_b10_admin_can_reach_every_admin_screen(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.questions.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk();
    }

    /** B11 — promoting a member to expert grants the new capability. */
    public function test_b11_admin_can_change_a_users_role(): void
    {
        $target = $this->disposableMember();

        $this->actingAs($this->admin())
            ->put(route('admin.users.role', $target), ['role_id' => $this->role('expert')->id])
            ->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertSame($this->role('expert')->id, $target->role_id);
        $this->actingAs($target)->get(route('jobs.create'))->assertOk();
    }

    /** B12 — an admin cannot change or deactivate their own account. */
    public function test_b12_admin_cannot_lock_themselves_out(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.role', $admin), [
            'role_id' => $this->role('member')->id,
        ]);

        $this->actingAs($admin)->put(route('admin.users.active', $admin));

        $admin->refresh();

        $this->assertTrue($admin->isAdmin(), 'The admin demoted themselves.');
        $this->assertTrue((bool) $admin->is_active, 'The admin deactivated themselves.');
    }

    /** B13 — a deactivated user can neither sign in nor keep a live session. */
    public function test_b13_deactivated_user_is_locked_out(): void
    {
        $target = $this->disposableMember();

        $this->actingAs($this->admin())
            ->put(route('admin.users.active', $target))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $target->fresh()->is_active);
        $this->signOut();

        $this->post(route('login.store'), [
            'email'    => $target->email,
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->assertGuest();

        // An already-open session is ejected on its next request.
        $this->actingAs($target->fresh())->get(route('dashboard'))->assertRedirect();
    }

    /** B14 — reactivating restores access, and the member loses expert links. */
    public function test_b14_reactivating_restores_member_access(): void
    {
        $target = $this->disposableMember();
        $admin  = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.active', $target));
        $this->actingAs($admin)->put(route('admin.users.active', $target));

        $this->assertTrue((bool) $target->fresh()->is_active);
        $this->signOut();

        $this->post(route('login.store'), [
            'email'    => $target->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $this->get(route('jobs.create'))->assertForbidden();
    }
}
