<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PackageItemData extends Data
{
    public function __construct(
        public ?int $itemId = null,
        public ?int $modelId = null,
        public ?string $itemSku = null,
        public ?int $modelQuantity = null,
        public ?int $orderItemId = null,
        public ?string $productLocationId = null,
        public ?int $promotionGroupId = null,
    ) {}
}
