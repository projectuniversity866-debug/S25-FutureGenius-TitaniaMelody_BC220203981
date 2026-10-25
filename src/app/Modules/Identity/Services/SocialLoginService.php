<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\Profile;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\SocialAccount;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as ProviderUser;

/**
 * FR1 "Social login" with Google and GitHub.
 *
 * Who is signed in, in this order:
 *   1. the account already linked to this provider identity;
 *   2. an existing account with the same email — linked now, but only when
 *      the provider has verified that email (otherwise anyone could claim
 *      someone else's account by putting their address on a social profile);
 *   3. a new Member account, created with the provider's verified email.
 */
class SocialLoginService
{
    public const PROVIDERS = ['google' => 'Google', 'github' => 'GitHub'];

    /**
     * Providers that have OAuth keys configured. Buttons are only shown for
     * these, so an unconfigured provider never offers a broken sign-in.
     *
     * @return array<string, string>
     */
    public function enabled(): array
    {
        return array_filter(self::PROVIDERS, fn ($label, $key) => filled(config("services.$key.client_id"))
            && filled(config("services.$key.client_secret")), ARRAY_FILTER_USE_BOTH);
    }

    /** @throws ValidationException when the account cannot be used */
    public function resolveUser(string $provider, ProviderUser $identity): User
    {
        $email = Str::lower((string) $identity->getEmail());

        if (! $this->emailIsVerified($provider, $identity) || $email === '') {
            throw ValidationException::withMessages([
                'email' => self::PROVIDERS[$provider].' did not share a verified email address, so we cannot sign you in with it.',
            ]);
        }

        $user = DB::transaction(function () use ($provider, $identity, $email) {
            $account = SocialAccount::where('provider', $provider)->where('provider_id', $identity->getId())->first();

            if ($account) {
                $account->update(['email' => $email, 'avatar_url' => $identity->getAvatar()]);

                return $account->user;
            }

            $user = User::where('email', $email)->first() ?? $this->createMember($identity, $email);

            $user->socialAccounts()->create([
                'provider'    => $provider,
                'provider_id' => $identity->getId(),
                'email'       => $email,
                'avatar_url'  => $identity->getAvatar(),
            ]);

            // The provider has confirmed the address.
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

            return $user;
        });

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        return $user;
    }

    /**
     * Socialite's GitHub driver only ever returns the primary *verified*
     * email. Google states it in the "email_verified" claim.
     */
    private function emailIsVerified(string $provider, ProviderUser $identity): bool
    {
        return match ($provider) {
            'github' => true,
            'google' => (bool) ($identity->user['email_verified'] ?? $identity->user['verified_email'] ?? false),
            default  => false,
        };
    }

    private function createMember(ProviderUser $identity, string $email): User
    {
        $user = User::create([
            'name'      => $identity->getName() ?: ($identity->getNickname() ?: Str::before($email, '@')),
            'email'     => $email,
            // Never usable as a password; the user signs in with the provider.
            'password'  => Str::password(40),
            'role_id'   => Role::where('slug', Role::MEMBER)->value('id'),
            'is_active' => true,
        ]);

        Profile::create(['user_id' => $user->id]);

        return $user;
    }
}
