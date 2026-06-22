<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A TW 3PL drop-off partner (get_shipping_parameter `response.dropoff.slug_list`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingSlugData extends Data
{
    public function __construct(
        public ?string $slug = null,
        public ?string $slugName = null,
    ) {}
}
