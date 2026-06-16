<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    use Queueable;

    public function __construct($app) {}

    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
