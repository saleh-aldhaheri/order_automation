<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `filter` object for a search_package_list request.
 *
 * Request input — `toArray()` emits snake_case keys. Null fields are stripped
 * before sending (see SearchPackageList::defaultBody).
 */
#[MapName(SnakeCaseMapper::class)]
class SearchPackageFilterData extends Data
{
    /**
     * @param  array<int, string>|null  $productLocationIds
     * @param  array<int, int>|null  $logisticsChannelIds
     */
    public function __construct(
        // 2 = ToProcess (to ship).
        public ?int $packageStatus = null,
        public ?array $productLocationIds = null,
        public ?array $logisticsChannelIds = null,
        public ?int $fulfillmentType = null,
        public ?bool $invoicePending = null,
        public ?int $sortingGroup = null,
        public ?int $orderType = null,
        public ?int $isPreOrder = null,
        public ?int $shippingPriority = null,
    ) {}
}
