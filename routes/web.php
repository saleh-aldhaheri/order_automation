<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ExternalSystemController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\SocialiteController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')
    ->as('auth.')
    ->group(function () {

        Route::get('/', [AuthController::class, 'login'])->name('login');
        Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');

        Route::get('/set-password', [AuthController::class, 'showSetPassword'])
            ->middleware('signed')
            ->name('set-password.show');

        Route::post('/set-password', [AuthController::class, 'updateSetPassword'])
            ->name('set-password.update');

        Route::prefix('{provider}')->as('social')->group(function () {
            Route::get('/redirect', [SocialiteController::class, 'redirect'])->name('.redirect');
            Route::get('/callback', [SocialiteController::class, 'callback'])->name('.social.callback');
        })->whereIn('provider', ['google']);
    });

Route::middleware('auth')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/email/verify', function () {
            return view('auth.verify-email');
        })->name('verification.notice');

        Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            return redirect()->route('profile.edit');
        })->middleware('signed')->name('verification.verify');

        Route::middleware('verified')->group(function () {
            // super admin only routes
            Route::middleware(RolesEnum::SUPER_ADMIN->middleware())->group(function () {

                Route::middleware(PermissionsEnum::PERMISSION_VIEW->middleware())
                    ->group(function () {
                        Route::resource('permissions', PermissionController::class)
                            ->only(['index', 'store', 'update', 'destroy'])
                            ->middlewareFor('store', PermissionsEnum::PERMISSION_CREATE->middleware())
                            ->middlewareFor('update', PermissionsEnum::PERMISSION_UPDATE->middleware())
                            ->middlewareFor('destroy', PermissionsEnum::PERMISSION_DELETE->middleware());
                    });

                Route::middleware(PermissionsEnum::EXTERNAL_SYSTEM_VIEW->middleware())
                    ->group(function () {
                        Route::post('/generate_token/{externalSystem}', [ExternalSystemController::class, 'generateToken'])
                            ->middleware(PermissionsEnum::EXTERNAL_SYSTEM_GENERATE_TOKEN->middleware())
                            ->name('external-systems.generate-token');
                        Route::put('/rotate_client_secret/{externalSystem}', [ExternalSystemController::class, 'rotateClientSecret'])
                            ->middleware(PermissionsEnum::EXTERNAL_SYSTEM_ROTATE_SECRET->middleware())
                            ->name('external-systems.rotate-client-secret');
                        Route::post('/revoke_token/{externalSystem}', [ExternalSystemController::class, 'revokeToken'])
                            ->middleware(PermissionsEnum::EXTERNAL_SYSTEM_REVOKE_TOKEN->middleware())
                            ->name('external-systems.revoke-token');
                        Route::resource('external-systems', ExternalSystemController::class)
                            ->except(['edit', 'create', 'show'])
                            ->middlewareFor('update', PermissionsEnum::EXTERNAL_SYSTEM_UPDATE->middleware())
                            ->middlewareFor('store', PermissionsEnum::EXTERNAL_SYSTEM_CREATE->middleware())
                            ->middlewareFor('destroy', PermissionsEnum::EXTERNAL_SYSTEM_DELETE->middleware());
                    });
            });

            Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

            Route::middleware(PermissionsEnum::ROLE_VIEW->middleware())
                ->group(function () {
                    Route::resource('roles', RoleController::class)
                        ->middlewareFor(['create', 'store'], PermissionsEnum::ROLE_CREATE->middleware())
                        ->middlewareFor(['edit', 'update'], PermissionsEnum::ROLE_UPDATE->middleware())
                        ->middlewareFor('destroy', PermissionsEnum::ROLE_DELETE->middleware());
                });

            Route::middleware(PermissionsEnum::USER_VIEW->middleware())
                ->group(function () {
                    Route::resource('users', UserController::class)
                        ->middlewareFor(['create', 'store'], PermissionsEnum::USER_CREATE->middleware())
                        ->middlewareFor(['edit', 'update'], PermissionsEnum::USER_UPDATE->middleware())
                        ->middlewareFor('destroy', PermissionsEnum::USER_DELETE->middleware());

                    Route::post('users/{user}/send-email-verification', [UserController::class, 'sendEmailVerification'])
                        ->middleware(PermissionsEnum::SEND_INVITATION->middleware())
                        ->name('users.send-email-verification');

                    Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])
                        ->middleware(PermissionsEnum::SEND_INVITATION->middleware())
                        ->name('users.resend-invitation');
                });

            Route::middleware(PermissionsEnum::SETTINGS_VIEW->middleware())
                ->group(function () {
                    Route::singleton('settings', SettingController::class)
                        ->only(['edit', 'update'])
                        ->middleware(PermissionsEnum::SETTINGS_UPDATE->middleware());
                });

        });
    });
