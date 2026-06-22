<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\ShopeeController;

Route::post('/shopee', ShopeeController::class)
    ->middleware('shopee_webhook')
    ->name('shopee');
