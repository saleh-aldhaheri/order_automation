<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee search_package_list `response` object.
 *
 * Faithful vendor DTO: one page of matching packages plus pagination. Walk
 * pages while `pagination->more` is true, passing `pagination->nextCursor`
 * back as the request cursor.
 */
#[MapInputName(SnakeCaseMapper::class)]
class SearchPackageListData extends Data
{
    /**
     * @param  array<int, SearchPackageListItemData>  $packagesList
     */
    public function __construct(
        #[DataCollectionOf(SearchPackageListItemData::class)]
        public array $packagesList = [],
        public ?SearchPackagePaginationData $pagination = null,
    ) {}
}
