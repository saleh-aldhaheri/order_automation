<?php

namespace App\Data\Integrations\Responses;

use Spatie\LaravelData\Data;

/**
 * A selectable drop-off branch, vendor-neutral.
 *
 * `id` is the opaque token the seller's choice round-trips back as (Shopee's
 * `branch_id`).
 */
class DropoffBranchOption extends Data
{
    public function __construct(
        public string $id,
        public ?string $address = null,
        public ?string $region = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?string $zipcode = null,
    ) {}
}
