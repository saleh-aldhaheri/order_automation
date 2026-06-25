<?php

namespace App\Data\Integrations\Requests;

use App\Enums\OrderStatusEnum;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use Spatie\LaravelData\Data;

class SyncOrderRequestData extends Data
{
    public function __construct(
        public string $externalOrderId,
        public ?string $externalOrderStatus = null,
        public ?OrderStatusEnum $orderStatus = null,
    ) {}


    public static function fromShopee(array $data): self
    {
        $externalOrderStatus = null;

        if (data_get($data['data'], 'status')) {
            $externalOrderStatus = ShopeeOrderStatusEnum::tryFrom(data_get($data['data'], 'status'));
        }

        return new self(
            externalOrderId: data_get($data['data'], 'ordersn'),
            externalOrderStatus: $externalOrderStatus ? $externalOrderStatus?->value :  null,
            orderStatus: $externalOrderStatus ? OrderStatusEnum::fromShopee($externalOrderStatus) : null
        );
    }
}
