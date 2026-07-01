<?php

namespace App\Providers;

use App\Integrations\Shopee\Events\ShopeeEvent;
use App\Listeners\Integrations\ShopeeOrderStatusListener;
use App\Listeners\Integrations\ShopeePackageStatusListener;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ShopeeEvent::class => [
            ShopeeOrderStatusListener::class,
            ShopeePackageStatusListener::class,
        ],
    ];

    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
