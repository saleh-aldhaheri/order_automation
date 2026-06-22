<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An available pickup address (get_shipping_parameter
 * `response.pickup.address_list`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingAddressData extends Data
{
    /**
     * @param  array<int, string>|null  $addressFlag
     * @param  array<int, ShippingTimeSlotData>|null  $timeSlotList
     */
    public function __construct(
        public ?int $addressId = null,
        public ?string $region = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $town = null,
        public ?string $address = null,
        public ?string $zipcode = null,
        public ?array $addressFlag = null,
        #[DataCollectionOf(ShippingTimeSlotData::class)]
        public ?array $timeSlotList = null,
    ) {}
}
