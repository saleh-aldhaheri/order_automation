<?php

namespace App\Data\Integrations\Responses;

use App\Enums\ShopsEnum;
use Spatie\LaravelData\Data;

/**
 * Neutral, vendor-agnostic representation of a package (parcel) inside an order.
 *
 * Mirrors the `packages` table. Each vendor translates its own parcel payload
 * into this shape in its service. `externalOrderId` is denormalised for fast
 * webhook lookups; `orderId` is the internal FK and is null until the parent
 * order row exists.
 */
class PackageResponse extends Data
{
    public function __construct(
        public string $externalPackageId,
        public string $externalOrderId,
        public ShopsEnum $shopType,
        public string $externalPackageStatus,
        public array $details,
        public string $packageStatus = 'pending',
        public ?int $orderId = null,
    ) {}
}
