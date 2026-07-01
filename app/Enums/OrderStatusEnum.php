<?php

namespace App\Enums;

use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;

enum OrderStatusEnum: string
{
    case PROCESSED = 'processed';
    case UNPROCESSED = 'unprocessed';
    case CANCELLED = 'cancelled';
    case RETURNING = 'returning';

    public static function fromShopee(ShopeeOrderStatusEnum $shopeeOrderStatus): OrderStatusEnum
    {
        return match ($shopeeOrderStatus) {
            ShopeeOrderStatusEnum::UNPAID,
            ShopeeOrderStatusEnum::READY_TO_SHIP => OrderStatusEnum::UNPROCESSED,
            ShopeeOrderStatusEnum::PROCESSED,
            ShopeeOrderStatusEnum::SHIPPED,
            ShopeeOrderStatusEnum::TO_CONFIRM_RECEIVE,
            ShopeeOrderStatusEnum::COMPLETED,
            ShopeeOrderStatusEnum::RETRY_SHIP => OrderStatusEnum::PROCESSED,
            ShopeeOrderStatusEnum::IN_CANCEL,
            ShopeeOrderStatusEnum::CANCELLED => OrderStatusEnum::CANCELLED,
            ShopeeOrderStatusEnum::TO_RETURN => OrderStatusEnum::RETURNING,
        };
    }
}
