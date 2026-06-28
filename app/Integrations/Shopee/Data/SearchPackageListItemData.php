<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single package from search_package_list `response.packages_list`.
 *
 * `isShipmentArranged` (only when LOGISTICS_READY) is the idempotency guard —
 * true means processing already started, don't double-ship.
 */
#[MapInputName(SnakeCaseMapper::class)]
class SearchPackageListItemData extends Data
{
    public function __construct(
        public string $orderSn,
        // Always returned by search_package_list — the parcel linking key.
        public string $packageNumber,
        public ?int $logisticsChannelId = null,
        // Warehouse — pass to the mass shipping step.
        public ?string $productLocationId = null,
        // [TW 30029 only] populated after arrangement.
        public ?string $sortingGroup = null,
        public ?bool $isShipmentArranged = null,
    ) {}
}
