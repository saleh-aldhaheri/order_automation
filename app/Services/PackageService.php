<?php

namespace App\Services;

use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\SyncPackageRequestData;
use App\Models\Order;
use App\Models\Package;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;
use Closure;
use Illuminate\Support\Facades\DB;

class PackageService
{
    private  ShopContract $shopService;
    private Shop $shop;

    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;

        $this->shopService = $shop->shop_type->service()::make($shop);

        return $this;
    }

    /**
     * Stamp an existing parcel's status from a push.
     *
     * Returns false when the parcel isn't known locally, signalling the caller
     * to run a full {@see syncPackage()} rebuild instead.
     */
    public function updatePackageStatus(SyncPackageRequestData $package): bool
    {
        $query = Package::query()
            ->where('external_package_id', $package->externalPackageId)
            ->where('shop_type', $this->shop->shop_type->value);

        if ($package->externalOrderId) {
            $query->where('external_order_id', $package->externalOrderId);
        }

        $existing = $query->first();

        if (!$existing) {
            return false;
        }

        $existing->fill([
            'external_package_status' => $package->externalPackageStatus,
            'package_status' => $package->externalPackageStatus, // temporary for now
        ])->save();

        return true;
    }

    /**
     * Rebuild an order's parcels from the marketplace.
     *
     * Used when a pushed package number isn't known locally (e.g. the order was
     * (re)split). `$beforeSync` lets the caller clear the order's current parcels
     * first (e.g. `fn() => $order->packages()->delete()`). The fetch happens
     * before the transaction; the clear + rebuild run inside it so the order is
     * never left without parcels.
     */
    public function syncPackage(SyncPackageRequestData $package, Order $order, ?Closure $beforeSync = null): void
    {
        $packages = $this->shopService
            ->getOrderPackages(new GetOrderRequestData([$package->externalOrderId]));

        DB::transaction(function () use ($packages, $order, $beforeSync) {
            if ($beforeSync) {
                ($beforeSync)();
            }

            foreach ($packages as $parcel) {
                Package::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'external_package_id' => $parcel->externalPackageId,
                    ],
                    [
                        'external_order_id' => $parcel->externalOrderId,
                        'shop_type' => $parcel->shopType->value,
                        'external_package_status' => $parcel->externalPackageStatus,
                        'package_status' => $parcel->packageStatus,
                        'details' => $parcel->details,
                    ]
                );
            }
        });
    }
}
