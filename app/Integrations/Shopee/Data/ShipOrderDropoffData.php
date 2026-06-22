<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `dropoff` object for a ship_order request.
 *
 * Required when get_shipping_parameter's `info_needed` lists dropoff (include
 * even if empty). Request input — `toArray()` emits snake_case.
 */
#[MapName(SnakeCaseMapper::class)]
class ShipOrderDropoffData extends Data
{
    public function __construct(
        public ?int $branchId = null,
        public ?string $senderRealName = null,
        public ?string $trackingNumber = null,
        public ?string $slug = null,
    ) {}
}
