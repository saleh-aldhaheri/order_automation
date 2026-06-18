<?php

namespace App\Data\Integrations\Shopee;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RecipientAddressData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $town = null,
        public ?string $district = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $region = null,
        public ?string $zipcode = null,
        public ?string $fullAddress = null,
    ) {}
}
