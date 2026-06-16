<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    public function setDynamicRedirect(Request $request, string $provider): void
    {
        $base = $request->getSchemeAndHttpHost();
        config([
            "services.{$provider}.redirect" => "{$base}/{$provider}/callback",
        ]);
    }

    public function handleCallback(Request $request, string $provider): void
    {
        $this->setDynamicRedirect($request, $provider);

        $socialUser = Socialite::driver($provider)->user();

        $email = $socialUser->getEmail();
        if ($email === null || $email === '') {
            $this->failSocialLogin([
                __(
                    'Your Google account did not share an email address. Allow email access or use email and password to sign in.'
                ),
            ]);
        }

        $dbUser = User::query()
            ->where('email', $email)
            ->first();

        if ($dbUser === null) {
            $this->failSocialLogin([
                __(
                    'No user exists for :email yet. Ask an administrator to invite you first, then try Google sign-in again or use email and password.',
                    ['email' => $email]
                ),
            ]);
        }

        if ($dbUser->provider === null && $dbUser->provider_id === null) {
            $dbUser->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
        } elseif (
            $dbUser->provider !== $provider
            || (string) $dbUser->provider_id !== (string) $socialUser->getId()
        ) {
            $this->failSocialLogin([
                __(
                    'This account is linked to a different sign-in method. Use email and password or the :provider account you used when linking.',
                    ['provider' => $provider]
                ),
            ]);
        }

        Auth::guard('web')->login($dbUser);
    }

    /**
     * @param  array<int, string>  $messages
     */
    private function failSocialLogin(array $messages): never
    {
        throw ValidationException::withMessages([
            'email' => $messages,
        ])->redirectTo(route('auth.login'));
    }
}
