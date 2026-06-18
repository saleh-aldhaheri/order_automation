<?php

namespace App\Listeners;

use App\Data\Integrations\Shopee\OrderStatusPushData;
use App\Enums\Integrations\ShopeeEventsEnum;
use App\Enums\ProvidersEnum;
use App\Models\Provider;
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

        $provider = Provider::query()
            ->where('provider_id', $data->shopId)
            ->where('provider_type', ProvidersEnum::SHOPEE->value)
            ->first();

        if (!$provider) {
            return;
        }

        ShopeeService::make($provider)->handleOrderStatus($data);
    }
}
