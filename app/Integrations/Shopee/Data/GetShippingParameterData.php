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
        // Always returned on success — it's the map of which methods are supported.
        public ShippingInfoNeededData $infoNeeded,
        // Conditional: only present when that method is actually supported.
        public ?ShippingDropoffData $dropoff = null,
        public ?ShippingPickupData $pickup = null,
    ) {}
}
