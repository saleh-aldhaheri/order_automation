<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A package created by split_order (`response.package_list`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class SplitOrderResultPackageData extends Data
{
    /**
     * @param  array<int, mixed>|null  $itemList
     */
    public function __construct(
        public string $packageNumber,
        public ?array $itemList = null,
    ) {}
}
