<?php

namespace App\Integrations\Shopee\Data;

use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One `order_list` entry for a get_shipping_document_result request.
 *
 * Request input. Omit `packageNumber` (null) for non-split — dropped from the
 * body, never sent "". `shippingDocumentType` must be the same type used at
 * create_shipping_document (NORMAL_AIR_WAYBILL, THERMAL_AIR_WAYBILL,
 * NORMAL_JOB_AIR_WAYBILL, THERMAL_JOB_AIR_WAYBILL, THERMAL_UNPACKAGED_LABEL).
 */
#[MapName(SnakeCaseMapper::class)]
class GetShippingDocumentResultOrderData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
        public ?ShopeeShippingDocumentTypeEnum $shippingDocumentType = null,
    ) {}
}
