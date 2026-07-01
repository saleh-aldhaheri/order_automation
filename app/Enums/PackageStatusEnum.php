<?php

namespace App\Enums;

use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;

enum PackageStatusEnum: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case LOST = 'lost';

    /**
     * Map a Shopee logistics/fulfillment status string into the app's neutral
     * package status. Both the order-detail `logistics_status` and the
     * PACKAGE_FULFILLMENT push `fulfillment_status` share the `LOGISTICS_*`
     * vocabulary, so a single mapper covers both write paths.
     *
     * Unknown / not-yet-started statuses fall back to {@see self::PENDING}.
     */
    public static function fromShopee(?string $shopeeStatus): self
    {
        return match ($shopeeStatus) {
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY => self::READY,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CREATED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_DONE,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_RETRY => self::SHIPPED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE => self::DELIVERED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_FAILED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_FAILED => self::FAILED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_INVALID,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CANCELED => self::CANCELLED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_LOST => self::LOST,
            default => self::PENDING,
        };
    }
}
