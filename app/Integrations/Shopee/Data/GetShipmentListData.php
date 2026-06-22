<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee get_shipment_list `response` object.
 *
 * Faithful vendor DTO: one page of shippable orders plus the pagination cursor.
 * Walk pages while `more` is true, passing `nextCursor` back as the request
 * cursor (`nextCursor` is empty when `more` is false).
 */
#[MapInputName(SnakeCaseMapper::class)]
class GetShipmentListData extends Data
{
    /**
     * @param  array<int, ShipmentListItemData>  $orderList
     */
    public function __construct(
        #[DataCollectionOf(ShipmentListItemData::class)]
        public array $orderList = [],
        public bool $more = false,
        public ?string $nextCursor = null,
    ) {}
}
