<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Optional `sort` object for a search_package_list request.
 */
#[MapName(SnakeCaseMapper::class)]
class SearchPackageSortData extends Data
{
    public function __construct(
        public ?int $sortType = null,
        public ?bool $ascending = null,
    ) {}
}
