<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One package to split an order into (split_order request input).
 *
 * Max parcels per request: 30 (TW) / 5 (other markets).
 */
#[MapName(SnakeCaseMapper::class)]
class SplitOrderPackageData extends Data
{
    /**
     * @param  array<int, SplitOrderItemData>  $itemList
     */
    public function __construct(
        #[DataCollectionOf(SplitOrderItemData::class)]
        public array $itemList,
    ) {}
}
