<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee split_order `response` object — the order with its newly created packages.
 */
#[MapInputName(SnakeCaseMapper::class)]
class SplitOrderData extends Data
{
    /**
     * @param  array<int, SplitOrderResultPackageData>  $packageList
     */
    public function __construct(
        public string $orderSn,
        #[DataCollectionOf(SplitOrderResultPackageData::class)]
        public array $packageList = [],
    ) {}
}
