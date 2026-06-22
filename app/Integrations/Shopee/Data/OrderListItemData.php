<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single entry from Shopee's get_order_list `response.order_list`.
 *
 * `order_status` is only present when requested via `response_optional_fields`.
 */
#[MapInputName(SnakeCaseMapper::class)]
class OrderListItemData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $orderStatus = null,
    ) {}
}
