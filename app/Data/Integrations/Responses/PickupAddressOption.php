<?php

namespace App\Data\Integrations\Responses;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * A selectable pickup address, vendor-neutral.
 *
 * `id` is the opaque token the seller's choice round-trips back as (Shopee's
 * `address_id`). `timeSlots` are the pickup windows available for this address.
 */
class PickupAddressOption extends Data
{
    /**
     * @param  array<int, PickupTimeSlotOption>  $timeSlots
     */
    public function __construct(
        public string $id,
        public ?string $address = null,
        public ?string $region = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        #[DataCollectionOf(PickupTimeSlotOption::class)]
        public array $timeSlots = [],
    ) {}
}
