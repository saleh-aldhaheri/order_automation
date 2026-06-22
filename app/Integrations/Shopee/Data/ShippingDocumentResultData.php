<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single entry from get_shipping_document_result `response.result_list`.
 *
 * `status` is PROCESSING / READY / FAILED — only downloadable when READY. Poll
 * until READY (or FAILED), or listen for shipping_document_status_push (code 15).
 * `failError` / `failMessage` are set per entry on failure.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ShippingDocumentResultData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
        public ?string $status = null,
        public ?string $failError = null,
        public ?string $failMessage = null,
    ) {}
}
