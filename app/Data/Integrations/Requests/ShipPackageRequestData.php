<?php

namespace App\Data\Integrations\Requests;

use App\Enums\ShippingMethodEnum;
use App\Models\Package;
use Spatie\LaravelData\Data;

/**
 * Vendor-neutral request to ship a package — the seller's selection from a
 * {@see \App\Data\Integrations\Responses\ShippingOptionsResponse}.
 *
 * STUB: this is the symmetric write side of the shipping flow. The frontend will
 * post back the chosen `method` plus the inputs that method required (the opaque
 * option ids from the options response). The marketplace service is the only
 * seam that maps these neutral fields into the vendor's ship_order body, so
 * `PackageService` never learns the vendor's field names.
 *
 * Only the fields relevant to the chosen `method` are expected to be set.
 */
class ShipPackageRequestData extends Data
{
    public function __construct(
        public Package $package, // prefer this to be object
        public ShippingMethodEnum $method,
        // pickup
        public ?string $pickupAddressId = null,
        public ?string $pickupTimeId = null,
        // dropoff
        public ?string $dropoffBranchId = null,
        // non-integrated / manual
        public ?string $trackingNumber = null,
        public ?string $senderName = null,
    ) {}
}
