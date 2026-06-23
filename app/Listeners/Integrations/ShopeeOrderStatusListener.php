<?php

namespace App\Listeners\Integrations;

use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Enums\ShopeeEventsEnum;
use App\Models\Shop;
use App\Services\OrderService;

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

        new OrderService()
            ->setShop($shop)
            ->syncShopOrder(SyncOrderRequestData::fromShopee($event->payload));
    }
}
