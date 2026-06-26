<?php

namespace App\Data\Integrations\Responses;

use Spatie\LaravelData\Data;

/**
 * A selectable pickup time slot, vendor-neutral.
 *
 * `id` is the opaque token the seller's choice round-trips back as (Shopee's
 * `pickup_time_id`). Prefer the slot flagged `recommended` when auto-selecting.
 */
class PickupTimeSlotOption extends Data
{
    public function __construct(
        public string $id,
        public ?int $date = null,
        public ?string $label = null,
        public bool $recommended = false,
    ) {}
}
