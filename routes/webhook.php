<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\webhook\ShopeeController;
use App\Http\Middleware\Integrations\ShopeeWebhookMiddleware;

Route::post('/shopee', ShopeeController::class)
    ->middleware(ShopeeWebhookMiddleware::class)
    ->name('shopee');
