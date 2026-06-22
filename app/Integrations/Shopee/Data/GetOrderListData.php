<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee get_order_list `response` object.
 *
 * Faithful vendor DTO: this is one page of order summaries plus the pagination
 * cursor. Walk pages while `more` is true, passing `nextCursor` back as the
 * request cursor.
 */
#[MapInputName(SnakeCaseMapper::class)]
class GetOrderListData extends Data
{
    /**
     * @param  array<int, OrderListItemData>  $orderList
     */
    public function __construct(
        #[DataCollectionOf(OrderListItemData::class)]
        public array $orderList = [],
        public bool $more = false,
        public ?string $nextCursor = null,
    ) {}
}
