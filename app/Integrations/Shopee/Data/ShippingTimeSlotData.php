<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A pickup time slot for an address (get_shipping_parameter
 * `response.pickup.address_list[].time_slot_list`).
 *
 * Prefer the slot whose `flags` contains "recommended" when auto-choosing.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingTimeSlotData extends Data
{
    /**
     * @param  array<int, string>|null  $flags
     */
    public function __construct(
        public ?int $date = null,
        public ?string $timeText = null,
        public ?string $pickupTimeId = null,
        public ?array $flags = null,
    ) {}
}
