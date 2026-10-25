<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Identity\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;

/**
 * Suite S — social login with Google and GitHub (FR1). The provider is
 * faked; everything after it (account matching, creation, guards) is real.
 * Cases S1–S8.
 */
class SocialLoginTest extends AqDeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-google', 'services.google.client_secret' => 'secret',
            'services.github.client_id' => 'test-github', 'services.github.client_secret' => 'secret',
        ]);
    }

    /** Make the provider "return" this identity on the callback. */
    private function fakeProvider(string $provider, array $identity, bool $fails = false): void
    {
        $user = (new ProviderUser)->setRaw($identity['raw'] ?? [])->map([
            'id'       => $identity['id'],
            'name'     => $identity['name'] ?? 'Social Tester',
            'nickname' => $identity['nickname'] ?? null,
            'email'    => $identity['email'],
            'avatar'   => 'https://example.test/avatar.png',
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $fails
            ? $driver->shouldReceive('user')->andThrow(new \RuntimeException('cancelled'))
            : $driver->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    /** S1 — a first-time GitHub user gets a new Member account and is signed in. */
    public function test_s1_new_github_user_is_registered_as_member(): void
    {
        $this->fakeProvider('github', ['id' => 'gh-1', 'name' => 'Ada Lovelace', 'email' => 'Ada@Example.test']);

        $this->get(route('social.callback', 'github'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'ada@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isMember());
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->profile);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'github', 'provider_id' => 'gh-1']);
    }

    /** S2 — signing in again with the same identity reuses the account. */
    public function test_s2_returning_user_reuses_the_linked_account(): void
    {
        $this->fakeProvider('github', ['id' => 'gh-2', 'email' => 'repeat@example.test']);
        $this->get(route('social.callback', 'github'));
        $this->signOut();
        $this->get(route('social.callback', 'github'));

        $this->assertSame(1, User::where('email', 'repeat@example.test')->count());
    }

    /** S3 — a verified Google email links to the existing account with that email. */
    public function test_s3_verified_google_email_links_existing_account(): void
    {
        $member = $this->member();
        $this->fakeProvider('google', ['id' => 'g-3', 'email' => $member->email, 'raw' => ['email_verified' => true]]);

        $this->get(route('social.callback', 'google'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($member);
        $this->assertSame(1, $member->socialAccounts()->where('provider', 'google')->count());
    }

    /** S4 — an unverified Google email is refused, so nobody can claim another person's account. */
    public function test_s4_unverified_google_email_is_refused(): void
    {
        $member = $this->member();
        $this->fakeProvider('google', ['id' => 'g-4', 'email' => $member->email, 'raw' => ['email_verified' => false]]);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, $member->socialAccounts()->count());
    }

    /** S5 — a deactivated account stays locked out through social login too. */
    public function test_s5_deactivated_user_cannot_use_social_login(): void
    {
        $target = $this->disposableMember();
        $target->update(['is_active' => false]);
        $this->fakeProvider('github', ['id' => 'gh-5', 'email' => $target->email]);

        $this->get(route('social.callback', 'github'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** S6 — cancelling on the provider's page returns to sign-in with a message. */
    public function test_s6_cancelled_sign_in_returns_to_login(): void
    {
        $this->fakeProvider('github', ['id' => 'x', 'email' => 'x@example.test'], fails: true);

        $this->get(route('social.callback', 'github'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'GitHub sign-in was cancelled or failed. Please try again.']);
    }

    /** S7 — buttons appear only for providers that have keys; others 404. */
    public function test_s7_buttons_only_for_configured_providers(): void
    {
        $this->get(route('login'))->assertSee('Continue with Google')->assertSee('Continue with GitHub');

        config(['services.google.client_id' => null]);

        $this->get(route('login'))->assertDontSee('Continue with Google')->assertSee('Continue with GitHub');
        $this->get(route('social.redirect', 'google'))->assertNotFound();
    }

    /** S8 — the sign-in redirect goes to the provider with our callback URL. */
    public function test_s8_redirect_goes_to_the_provider(): void
    {
        $this->get(route('social.redirect', 'github'))
            ->assertRedirect()
            ->assertRedirectContains('github.com/login/oauth/authorize')
            ->assertRedirectContains(urlencode(route('social.callback', 'github')));
    }
}
