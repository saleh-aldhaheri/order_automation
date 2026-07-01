<?php

use App\Http\Controllers\Webhook\ShopeeController;
use Illuminate\Support\Facades\Route;

Route::post('/shopee', ShopeeController::class)
    ->middleware('shopee_webhook')
    ->name('shopee');
