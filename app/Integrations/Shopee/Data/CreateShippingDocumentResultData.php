<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single entry from create_shipping_document `response.result_list`.
 *
 * `failError` / `failMessage` are set per entry when that order/package failed.
 */
#[MapInputName(SnakeCaseMapper::class)]
class CreateShippingDocumentResultData extends Data
{
    public function __construct(
        public string $orderSn,
        public ?string $packageNumber = null,
        public ?string $failError = null,
        public ?string $failMessage = null,
    ) {}
}
