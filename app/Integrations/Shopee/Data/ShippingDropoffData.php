<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Drop-off info (get_shipping_parameter `response.dropoff`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingDropoffData extends Data
{
    /**
     * @param  array<int, ShippingBranchData>|null  $branchList
     * @param  array<int, ShippingSlugData>|null  $slugList
     */
    public function __construct(
        #[DataCollectionOf(ShippingBranchData::class)]
        public ?array $branchList = null,
        #[DataCollectionOf(ShippingSlugData::class)]
        public ?array $slugList = null,
    ) {}
}
