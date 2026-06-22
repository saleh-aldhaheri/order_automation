<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single entry from get_shipping_document_parameter `response.result_list`.
 *
 * Don't hardcode the document type — use `suggestShippingDocumentType` (varies
 * per order/channel). `failError` / `failMessage` are set per entry on failure.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingDocumentParameterResultData extends Data
{
    /**
     * @param  array<int, string>|null  $selectableShippingDocumentType
     */
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
        public ?string $suggestShippingDocumentType = null,
        public ?array $selectableShippingDocumentType = null,
        public ?string $failError = null,
        public ?string $failMessage = null,
    ) {}
}
