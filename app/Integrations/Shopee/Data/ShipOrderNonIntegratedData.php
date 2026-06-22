<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `non_integrated` object for a ship_order request.
 *
 * Required when get_shipping_parameter's `info_needed` lists non_integrated.
 * `trackingNumber` is the seller's own courier tracking. Request input.
 */
#[MapName(SnakeCaseMapper::class)]
class ShipOrderNonIntegratedData extends Data
{
    public function __construct(
        public ?string $trackingNumber = null,
    ) {}
}
