<?php

namespace App\Data\Integrations\Shopee;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class OrderItemData extends Data
{
    /**
     * @param  array<int, string>|null  $productLocationId
     */
    public function __construct(
        public ?int $itemId = null,
        public ?string $itemName = null,
        public ?string $itemSku = null,
        public ?int $modelId = null,
        public ?string $modelName = null,
        public ?string $modelSku = null,
        public ?int $modelQuantityPurchased = null,
        public ?float $modelDiscountedPrice = null,
        public ?float $modelOriginalPrice = null,
        public ?bool $wholesale = null,
        public ?float $weight = null,
        public ?int $orderItemId = null,
        public ?int $promotionId = null,
        public ?string $promotionType = null,
        public ?int $promotionGroupId = null,
        public ?bool $addOnDeal = null,
        public ?int $addOnDealId = null,
        public ?bool $mainItem = null,
        public ?bool $isB2cOwnedItem = null,
        public ?bool $isPrescriptionItem = null,
        public ?array $productLocationId = null,
        public ?ImageInfoData $imageInfo = null,
    ) {}
}
