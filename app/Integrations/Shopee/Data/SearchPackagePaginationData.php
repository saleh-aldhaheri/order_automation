<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * `pagination` object from a search_package_list `response`.
 */
#[MapInputName(SnakeCaseMapper::class)]
class SearchPackagePaginationData extends Data
{
    public function __construct(
        public ?int $totalCount = null,
        public ?string $nextCursor = null,
        public bool $more = false,
    ) {}
}
