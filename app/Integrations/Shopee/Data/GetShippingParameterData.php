<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee get_shipping_parameter `response` object.
 *
 * Faithful vendor DTO. Read `infoNeeded` first to learn which method (pickup /
 * dropoff / non_integrated) the package supports, then read the matching
 * `pickup` / `dropoff` block for the concrete options to feed into ship_order.
 */
#[MapInputName(SnakeCaseMapper::class)]
class GetShippingParameterData extends Data
{
    public function __construct(
        public ?ShippingInfoNeededData $infoNeeded = null,
        public ?ShippingDropoffData $dropoff = null,
        public ?ShippingPickupData $pickup = null,
    ) {}
}
