<?php

namespace App\Data\Integrations\Responses;

use App\Enums\ShopsEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Vendor-neutral shipping options for a single package.
 *
 * Each marketplace translates its own "shipping parameter" payload into this
 * shape in its service, so callers ({@see \App\Services\PackageService}) and the
 * frontend never see marketplace-specific structures. `methods` holds every
 * fulfilment method the package supports — the seller picks one, then provides
 * that method's required inputs, which round-trip into a ship request.
 */
class ShippingOptionsResponse extends Data
{
    /**
     * @param  array<int, ShippingMethodOption>  $methods
     */
    public function __construct(
        public string $externalOrderId,
        public ?string $externalPackageId,
        public ShopsEnum $shopType,
        #[DataCollectionOf(ShippingMethodOption::class)]
        public array $methods = [],
    ) {}
}
