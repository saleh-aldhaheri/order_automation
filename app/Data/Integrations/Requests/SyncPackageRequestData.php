<?php

namespace App\Data\Integrations\Requests;

use Spatie\LaravelData\Data;

/**
 * Neutral input for syncing a single package's status from a marketplace push.
 *
 * Built from a vendor webhook (e.g. Shopee's PACKAGE_FULFILLMENT push, code 30).
 * Carries just enough to locate the parcel (order + package id) and stamp its
 * latest status — full parcel details are fetched separately when the package is
 * seen for the first time.
 */
class SyncPackageRequestData extends Data
{
    public function __construct(
        public string $externalPackageId,
        public ?string $externalOrderId,
        public ?string $externalPackageStatus = null,
    ) {}

    public static function fromShopee(array $data): self
    {
        return new self(
            externalPackageId: data_get($data['data'], 'package_number'),
            externalOrderId: data_get($data['data'], 'ordersn'),
            externalPackageStatus: data_get($data['data'], 'fulfillment_status'),
        );
    }
}
