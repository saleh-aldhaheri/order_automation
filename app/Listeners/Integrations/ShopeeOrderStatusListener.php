<?php

namespace App\Listeners;

use App\Data\Integrations\Shopee\OrderStatusPushData;
use App\Enums\Integrations\ShopeeEventsEnum;
use App\Enums\ShopsEnum;
use App\Models\Shop;
use App\Services\Integrations\ShopeeService;

class ShopeeOrderStatusListener
{

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $code  = $event->code;

        if ($code !== ShopeeEventsEnum::ORDER_STATUS->value) {
            return;
        }

        $payload  = $event->payload;

        $data = OrderStatusPushData::fromArray($payload);

        if ($event !== ShopeeEventsEnum::ORDER_STATUS) {
            return;
        }

        $shop = Shop::query()
            ->where('external_shop_id', $data->shopId)
            ->where('shop_type', ShopsEnum::SHOPEE->value)
            ->first();

        if (!$shop) {
            return;
        }

        ShopeeService::make($shop)->handleOrderStatus($data);
    }
}
