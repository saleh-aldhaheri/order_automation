<?php

namespace App\Data\Integrations\Requests;

use App\Enums\OrderStatusEnum;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use Spatie\LaravelData\Data;

class SyncOrderRequestData extends Data
{
    public function __construct(
        public string $externalOrderId,
        public string $externalOrderStatus,
        public OrderStatusEnum $orderStatus,
    ) {}


    public static function fromShopee(array $data): self
    {
        $externalOrderStatus = ShopeeOrderStatusEnum::tryFrom(data_get($data['data'], 'order_status'));

        return new self(
            externalOrderId: data_get($data['data'], 'order_sn'),
            externalOrderStatus: $externalOrderStatus->value,
            orderStatus: OrderStatusEnum::fromShopee($externalOrderStatus),
        );
    }
}
