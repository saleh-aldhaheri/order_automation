<?php

use App\Http\Controllers\Api\ExternalSystemAuth;
use Illuminate\Support\Facades\Route;

Route::as('api.external-systems.')->group(function () {
    Route::post('/login', [ExternalSystemAuth::class, 'login'])->name('login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/revoke', [ExternalSystemAuth::class, 'revoke'])->name('revoke');
    });
});
