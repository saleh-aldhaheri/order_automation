<?php

namespace App\Data\Integrations\Responses;

use App\Enums\OrderStatusEnum;
use App\Enums\ShopsEnum;
use Spatie\LaravelData\Data;

/**
 *   $table->string('external_order_id');
            $table->foreignId('shop_id')
                ->constrained();
            $table->string('shop_type');
            $table->string('external_order_status');
            $table->string('order_status')
                ->default(OrderStatusEnum::UNPROCESSED->value);
            $table->json('details')->nullable();
 */

/**
 * Neutral, vendor-agnostic representation of an order the application understands.
 *
 * This DTO is pure data and knows about no marketplace. Each vendor translates
 * its own payload into this shape in its service
 * (e.g. {@see \App\Services\Integrations\ShopeeService::getOrder()}).
 */
class GetOrderResponseData extends Data
{
    public function __construct(
        public string $externalOrderId,
        public string $shopId,
        public ShopsEnum $shopType,
        public string $externalOrderStatus,
        public OrderStatusEnum $orderStatus,
        public array $details
    ) {}
}
