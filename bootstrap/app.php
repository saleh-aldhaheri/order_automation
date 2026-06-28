<?php

use App\Integrations\Shopee\Middlewares\ShopeeWebhookMiddleware;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

(new \Bugsnag\BugsnagLaravel\OomBootstrapper())->bootstrap();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/',
        then: function () {
            Route::prefix('webhook')
                ->as('webhook.')
                ->group(base_path('routes/webhook.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn(Request $request) => route('auth.login'));
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'shopee_webhook' => ShopeeWebhookMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('bugsnag')) {
                Bugsnag::notifyException($e);
            }
        });
        $exceptions->renderable(function (\App\Integrations\Shopee\Exceptions\ShopeeException $e) {
            Log::error("Shopee error: ", ["error" =>  $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Shopee service is temporarily unavailable. Please try again.');
        });
    })->create();
