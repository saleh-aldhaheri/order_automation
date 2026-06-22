<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A drop-off branch (get_shipping_parameter `response.dropoff.branch_list`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingBranchData extends Data
{
    public function __construct(
        public ?int $branchId = null,
        public ?string $region = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?string $address = null,
        public ?string $zipcode = null,
        public ?string $district = null,
        public ?string $town = null,
    ) {}
}
