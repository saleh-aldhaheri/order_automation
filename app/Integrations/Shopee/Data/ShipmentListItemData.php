<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single entry from Shopee's get_shipment_list `response.order_list`.
 *
 * These are orders at READY_TO_SHIP / RETRY_SHIP ready to start the shipping flow.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShipmentListItemData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
    ) {}
}
