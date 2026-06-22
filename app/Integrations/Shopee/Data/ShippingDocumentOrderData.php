<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One `order_list` entry for the waybill (shipping document) APIs.
 *
 * Request input. `packageNumber` is a SINGLE value (never comma-separated): for
 * a split order add one entry per package, all sharing the same `orderSn`. Omit
 * `packageNumber` (leave null) for a non-split order — it is dropped from the
 * body rather than sent as "".
 */
#[MapName(SnakeCaseMapper::class)]
class ShippingDocumentOrderData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
    ) {}
}
