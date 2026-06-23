<?php

namespace App\Data\Integrations\Responses;

use App\Enums\OrderStatusEnum;
use App\Enums\ShopsEnum;
use Spatie\LaravelData\Data;

/**
 * Neutral, vendor-agnostic representation of an order the application understands.
 *
 * This DTO is pure data and knows about no marketplace. Each vendor translates
 * its own payload into this shape in its service
 * (e.g. {@see \App\Services\Integrations\ShopeeService::getOrder()}).
 *
 * Mirrors the `orders` table, plus the order's parcels as a list of
 * {@see PackageResponse}.
 */
class OrderResponse extends Data
{
    /**
     * @param  array<int, PackageResponse>  $packageList
     */
    public function __construct(
        public string $externalOrderId,
        public string $shopId,
        public string $externalShopId,
        public ShopsEnum $shopType,
        public string $externalOrderStatus,
        public OrderStatusEnum $orderStatus,
        public array $details,
    ) {}
}
