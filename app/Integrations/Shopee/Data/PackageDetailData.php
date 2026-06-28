<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single package from Shopee's get_package_detail `response.package_list`.
 *
 * Faithful vendor DTO. `is_shipment_arranged` (when LOGISTICS_READY) is the
 * idempotency guard — true means processing already started, don't double-ship.
 */
#[MapInputName(SnakeCaseMapper::class)]
class PackageDetailData extends Data
{
    /**
     * @param  array<int, string>|null  $pendingTerms
     * @param  array<int, string>|null  $pendingDescription
     * @param  array<int, PackageItemData>|null  $itemList
     * @param  array<string, mixed>|null  $driverInfo
     */
    public function __construct(
        public string $orderSn,
        // Always returned by get_package_detail — parcel key + its fulfillment state.
        public string $packageNumber,
        public string $fulfillmentStatus,
        public ?int $updateTime = null,
        public ?int $logisticsChannelId = null,
        public ?string $shippingCarrier = null,
        public ?bool $allowSelfDesignAwb = null,
        public ?int $daysToShip = null,
        public ?int $shipByDate = null,
        public ?array $pendingTerms = null,
        public ?array $pendingDescription = null,
        public ?string $trackingNumber = null,
        public ?int $pickupDoneTime = null,
        public ?bool $isSplitUp = null,
        public ?bool $canSplitOrder = null,
        public ?bool $canUnsplitOrder = null,
        public ?bool $isShipmentArranged = null,
        public ?bool $isPreOrder = null,
        #[DataCollectionOf(PackageItemData::class)]
        public ?array $itemList = null,
        public ?RecipientAddressData $recipientAddress = null,
        public ?StatusInfoTagData $statusInfoTag = null,
        public ?int $preparationEndTime = null,
        public ?array $driverInfo = null,
        public ?bool $canFullCancelOrder = null,
        public ?bool $canPartialCancelOrder = null,
    ) {}
}
