<?php

namespace App\Providers;

use App\Integrations\Shopee\Events\ShopeeWebhookEvent;
use App\Listeners\Integrations\ShopeeOrderStatusListener;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ShopeeWebhookEvent::class => [
            ShopeeOrderStatusListener::class,
        ],
    ];

    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
