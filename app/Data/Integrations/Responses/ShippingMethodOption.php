<?php

namespace App\Data\Integrations\Responses;

use App\Enums\ShippingInputEnum;
use App\Enums\ShippingMethodEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * One fulfilment method available for a package, vendor-neutral.
 *
 * `requiredInputs` tells the frontend which inputs the seller must provide for
 * this method; `addresses` / `branches` carry the concrete options to choose
 * from (only the list relevant to {@see $method} is populated). NON_INTEGRATED
 * typically carries no lists — just a required tracking number.
 */
class ShippingMethodOption extends Data
{
    /**
     * @param  array<int, ShippingInputEnum>  $requiredInputs
     * @param  array<int, PickupAddressOption>  $addresses
     * @param  array<int, DropoffBranchOption>  $branches
     */
    public function __construct(
        public ShippingMethodEnum $method,
        public array $requiredInputs = [],
        #[DataCollectionOf(PickupAddressOption::class)]
        public array $addresses = [],
        #[DataCollectionOf(DropoffBranchOption::class)]
        public array $branches = [],
    ) {}
}
