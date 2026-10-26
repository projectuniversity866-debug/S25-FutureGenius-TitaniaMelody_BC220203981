<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Services\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with Google / GitHub". The callback address is built from the
 * route, so it is correct on WampServer sub-folders and on production alike.
 */
class SocialLoginController extends Controller
{
    public function __construct(private SocialLoginService $social) {}

    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(array_key_exists($provider, $this->social->enabled()), 404);

        return Socialite::driver($provider)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(array_key_exists($provider, $this->social->enabled()), 404);

        try {
            $identity = Socialite::driver($provider)
                ->redirectUrl(route('social.callback', $provider))
                ->user();
        } catch (Throwable) {
            // Cancelled on the provider's page, expired state, or a network error.
            return redirect()->route('login')->withErrors([
                'email' => SocialLoginService::PROVIDERS[$provider].' sign-in was cancelled or failed. Please try again.',
            ]);
        }

        try {
            $user = $this->social->resolveUser($provider, $identity);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('login'));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
