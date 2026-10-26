<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Identity\Models\User;

/**
 * Suite A — Authentication (Module 1). Cases A1–A8.
 */
class AuthenticationTest extends AqDeskTestCase
{
    /** A1 — a guest can register as a member and is signed in afterwards. */
    public function test_a1_guest_can_register_as_a_member(): void
    {
        $this->post(route('register.store'), [
            'name'                  => 'New Tester',
            'email'                 => 'new.tester@aqdesk.test',
            'role_slug'             => 'member', 'terms' => '1',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new.tester@aqdesk.test']);
        $this->assertAuthenticated();
    }

    /** A2 — a duplicate email is rejected and creates no second row. */
    public function test_a2_duplicate_email_is_rejected(): void
    {
        $existing = $this->member();

        $this->post(route('register.store'), [
            'name'                  => 'Impostor',
            'email'                 => $existing->email,
            'role_slug'             => 'member', 'terms' => '1',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', $existing->email)->count());
        $this->assertGuest();
    }

    /** A3 — a password shorter than eight characters is rejected. */
    public function test_a3_short_password_is_rejected(): void
    {
        $this->post(route('register.store'), [
            'name'                  => 'Short Password',
            'email'                 => 'short@aqdesk.test',
            'role_slug'             => 'member', 'terms' => '1',
            'password'              => 'abcd',
            'password_confirmation' => 'abcd',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'short@aqdesk.test']);
    }

    /** A4 — registering as an expert grants the job posting capability. */
    public function test_a4_registering_as_expert_can_post_opportunities(): void
    {
        $this->post(route('register.store'), [
            'name'                  => 'Expert Signup',
            'email'                 => 'expert.signup@aqdesk.test',
            'role_slug'             => 'expert', 'terms' => '1',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'expert.signup@aqdesk.test')->firstOrFail();

        $this->assertTrue($user->canPostJobs());

        // Publishing waits for the email to be confirmed (step 1.2).
        $this->actingAs($user)->get(route('jobs.create'))->assertRedirect(route('verification.notice'));

        $user->markEmailAsVerified();
        $this->actingAs($user->fresh())->get(route('jobs.create'))->assertOk();
    }

    /** A5 — a wrong password is refused. */
    public function test_a5_wrong_password_is_refused(): void
    {
        $this->post(route('login.store'), [
            'email'    => $this->member()->email,
            'password' => 'wrongpass',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    /** A6 — correct credentials sign the user in; remember me sets its cookie. */
    public function test_a6_correct_credentials_sign_in_with_remember_me(): void
    {
        $member = $this->member();

        $response = $this->post(route('login.store'), [
            'email'    => $member->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($member);
        $this->assertNotEmpty($member->fresh()->getRememberToken());
    }

    /** A7 — after signing out the dashboard is no longer reachable. */
    public function test_a7_dashboard_unreachable_after_signing_out(): void
    {
        $this->actingAs($this->member())->post(route('logout'));

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** A8 — a signed-in user visiting the login page is redirected away. */
    public function test_a8_signed_in_user_cannot_open_the_login_page(): void
    {
        $this->actingAs($this->member())->get(route('login'))->assertRedirect();
    }
}
