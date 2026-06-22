<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Pickup info (get_shipping_parameter `response.pickup`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingPickupData extends Data
{
    /**
     * @param  array<int, ShippingAddressData>|null  $addressList
     */
    public function __construct(
        #[DataCollectionOf(ShippingAddressData::class)]
        public ?array $addressList = null,
    ) {}
}
