<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('profile.edit');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('auth.login')
            ->with('status', __('You have been signed out.'));
    }

    public function showSetPassword(Request $request)
    {
        $user = User::query()->findOrFail($request->query('id'));

        if ($user->hasVerifiedEmail() || $user->password !== null) {
            return redirect()->route('auth.login');
        }

        return view('auth.set-password', [
            'user' => $user,
        ]);
    }

    public function updateSetPassword(Request $request)
    {
        if (! $request->hasValidSignatureWhileIgnoring(['password', 'password_confirmation', '_token'])) {
            return redirect()
                ->route('auth.login')
                ->with(
                    'error',
                    'This password link is invalid or has expired. Ask your administrator for a new invitation.',
                );
        }

        $validated = $request->validate(
            [
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers(),
                ],
            ],
            [
                'password.required' => 'Enter a new password.',
                'password.confirmed' => 'The two passwords do not match. Type the same password in both fields.',
            ],
        );

        $user = User::query()->findOrFail($request->query('id'));

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return redirect()
            ->route('auth.login')
            ->with('status', 'Your password is set. You can sign in now.');
    }
}
