<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `info_needed` from get_shipping_parameter — the params each shipping method
 * requires. Use exactly ONE method; whichever key is non-empty is the supported
 * method for this package (its array lists the fields ship_order needs).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingInfoNeededData extends Data
{
    /**
     * @param  array<int, string>|null  $dropoff
     * @param  array<int, string>|null  $pickup
     * @param  array<int, string>|null  $nonIntegrated
     */
    public function __construct(
        public ?array $dropoff = null,
        public ?array $pickup = null,
        public ?array $nonIntegrated = null,
    ) {}
}
