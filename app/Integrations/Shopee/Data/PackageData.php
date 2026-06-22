<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PackageData extends Data
{
    /**
     * @param  array<int, PackageItemData>|null  $itemList
     */
    public function __construct(
        public ?string $packageNumber = null,
        public ?string $logisticsStatus = null,
        public ?int $logisticsChannelId = null,
        public ?string $shippingCarrier = null,
        public ?int $parcelChargeableWeightGram = null,
        public ?string $groupShipmentId = null,
        public ?bool $allowSelfDesignAwb = null,
        public ?string $sortingGroup = null,
        #[DataCollectionOf(PackageItemData::class)]
        public ?array $itemList = null,
    ) {}
}
