<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware('guest')
    ->as('auth.')
    ->group(function () {
        Route::get('/login', [AuthController::class,  'login'])->name('login');
        Route::post('/authenticate', [AuthController::class,  'authenticate'])->name('authenticate');
    });
