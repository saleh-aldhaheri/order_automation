<?php

namespace App\Integrations\Shopee\Data;

use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One `order_list` entry for a create_shipping_document request.
 *
 * Request input. `packageNumber` is a SINGLE value (never comma-separated): for
 * a split order add one entry per package, all sharing the same `orderSn`. Omit
 * `packageNumber` (null) for non-split — dropped from the body, never sent "".
 *
 * `trackingNumber` is required except on channels that allow print-before-ship.
 * `shippingDocumentType`: one of NORMAL_AIR_WAYBILL, THERMAL_AIR_WAYBILL,
 * NORMAL_JOB_AIR_WAYBILL, THERMAL_JOB_AIR_WAYBILL, THERMAL_UNPACKAGED_LABEL
 * (use the value from get_shipping_document_parameter — don't hardcode).
 */
#[MapName(SnakeCaseMapper::class)]
class CreateShippingDocumentOrderData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
        public ?string $trackingNumber = null,
        public ?ShopeeShippingDocumentTypeEnum $shippingDocumentType = null,
    ) {}
}
