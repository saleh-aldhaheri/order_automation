<?php

namespace App\Listeners;

use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Enums\Integrations\ShopeeEventsEnum;
use App\Enums\ShopsEnum;
use App\Models\Shop;
use App\Services\ShopService;

class ShopeeOrderStatusListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event->eventType !== ShopeeEventsEnum::ORDER_STATUS) {
            return;
        }

        $shop = Shop::query()
            ->getShop($event->payload['shop_id'], ShopsEnum::SHOPEE)
            ->first();

        if (!$shop) {
            return;
        };

        new ShopService()
            ->setShop($shop)
            ->syncShopOrder(SyncOrderRequestData::fromShopee($event->payload));
    }
}
