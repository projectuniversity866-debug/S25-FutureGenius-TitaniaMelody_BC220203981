<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Suite V — email verification and password reset (FR1). Cases V1–V8.
 */
class AccountSecurityTest extends AqDeskTestCase
{
    private function register(string $email = 'fresh@aqdesk.test'): User
    {
        $this->post(route('register.store'), [
            'name' => 'Fresh Member', 'email' => $email, 'role_slug' => 'member',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasNoErrors();

        return User::where('email', $email)->firstOrFail();
    }

    /** V1 — registering sends exactly one confirmation email. */
    public function test_v1_registration_sends_one_verification_email(): void
    {
        Notification::fake();

        $user = $this->register();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    /** V2 — an unconfirmed account can browse but not post. */
    public function test_v2_unverified_user_cannot_post(): void
    {
        $user = $this->register();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Confirm your email address to start posting');
        $this->actingAs($user)->get(route('questions.create'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post(route('questions.store'), $this->questionPayload())->assertRedirect(route('verification.notice'));
        $this->assertSame(0, $user->questions()->count());
    }

    /** V3 — the signed link confirms the address; a tampered link does not. */
    public function test_v3_signed_link_verifies_and_tampered_link_fails(): void
    {
        $user = $this->register();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]);

        $this->actingAs($user)->get($url.'x')->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get(route('questions.create'))->assertOk();
    }

    /** V4 — "send the link again" sends a fresh email. */
    public function test_v4_resend_verification(): void
    {
        $user = $this->register();
        Notification::fake();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** V5 — changing your email requires confirming the new one. */
    public function test_v5_changing_email_resets_verification(): void
    {
        Notification::fake();
        $member = $this->member();

        $this->actingAs($member)->put(route('profile.update'), [
            'name' => $member->name, 'email' => 'new.address@aqdesk.test',
        ])->assertSessionHasNoErrors();

        $this->assertNull($member->fresh()->email_verified_at);
        Notification::assertSentTo($member, VerifyEmail::class);
    }

    /** V6 — forgot password emails a reset link, without revealing whether the address exists. */
    public function test_v6_forgot_password_sends_link_and_hides_unknown_addresses(): void
    {
        Notification::fake();
        $member = $this->member();

        $this->post(route('password.email'), ['email' => $member->email])->assertSessionHas('status');
        Notification::assertSentTo($member, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'nobody@aqdesk.test'])
            ->assertSessionHas('status')->assertSessionHasNoErrors();
    }

    /** V7 — the token resets the password; the old one stops working. */
    public function test_v7_reset_password_with_token(): void
    {
        Notification::fake();
        $member = $this->member();
        $this->post(route('password.email'), ['email' => $member->email]);

        $token = null;
        Notification::assertSentTo($member, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;
            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $member->email]))->assertOk();
        $this->post(route('password.store'), [
            'token' => $token, 'email' => $member->email,
            'password' => 'a-brand-new-password', 'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-brand-new-password', $member->fresh()->password));
    }

    /** V8 — a wrong token changes nothing. */
    public function test_v8_invalid_token_is_rejected(): void
    {
        $member = $this->member();

        $this->post(route('password.store'), [
            'token' => 'not-a-real-token', 'email' => $member->email,
            'password' => 'a-brand-new-password', 'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $member->fresh()->password));
    }
}
