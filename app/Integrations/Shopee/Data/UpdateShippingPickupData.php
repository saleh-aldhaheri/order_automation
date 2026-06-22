<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `pickup` object for an update_shipping_order request.
 *
 * Both fields are required here (unlike ship_order). Values come from
 * get_shipping_parameter. Request input — `toArray()` emits snake_case.
 */
#[MapName(SnakeCaseMapper::class)]
class UpdateShippingPickupData extends Data
{
    public function __construct(
        public int $addressId,
        public string $pickupTimeId,
    ) {}
}
