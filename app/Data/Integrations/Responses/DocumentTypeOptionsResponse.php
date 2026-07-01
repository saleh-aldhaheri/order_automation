<?php

namespace App\Data\Integrations\Responses;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Enums\ShopsEnum;
use App\Services\PackageService;
use Spatie\LaravelData\Data;

/**
 * Vendor-neutral list of waybill/document types a package can generate.
 *
 * Each marketplace translates its own "document parameter" payload into this
 * shape in its adapter, so callers ({@see PackageService}) and the
 * frontend never see marketplace-specific structures. The seller picks one of
 * `selectableTypes` (or accepts `suggestedType`), which round-trips into
 * {@see ShopAdapterContract::createDocument()}.
 */
class DocumentTypeOptionsResponse extends Data
{
    /**
     * @param  array<int, string>  $selectableTypes
     */
    public function __construct(
        public string $externalOrderId,
        public ?string $externalPackageId,
        public ShopsEnum $shopType,
        public ?string $suggestedType,
        public array $selectableTypes = [],
    ) {}
}
