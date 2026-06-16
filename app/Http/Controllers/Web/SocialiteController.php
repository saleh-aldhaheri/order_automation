<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SocialiteService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function __construct(
        private SocialiteService $socialiteService
    ) {}

    public function redirect(Request $request, string $provider)
    {
        $this->socialiteService->setDynamicRedirect($request, $provider);

        return Socialite::driver($provider)
            ->with([
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        $this->socialiteService->handleCallback($request, $provider);

        return redirect()->route('profile.edit');
    }
}
