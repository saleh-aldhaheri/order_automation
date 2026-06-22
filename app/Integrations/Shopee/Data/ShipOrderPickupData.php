<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `pickup` object for a ship_order request.
 *
 * Required when get_shipping_parameter's `info_needed` lists pickup. Values come
 * from get_shipping_parameter (`address_id` + one `pickup_time_id` from the
 * address's `time_slot_list`). Request input — `toArray()` emits snake_case.
 */
#[MapName(SnakeCaseMapper::class)]
class ShipOrderPickupData extends Data
{
    public function __construct(
        public int $addressId,
        public ?string $pickupTimeId = null,
        public ?string $trackingNumber = null,
    ) {}
}
