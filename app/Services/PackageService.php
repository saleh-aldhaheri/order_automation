<?php

namespace App\Services;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\ShipPackageRequestData;
use App\Data\Integrations\Requests\SyncPackageRequestData;
use App\Data\Integrations\Responses\ShippingOptionsResponse;
use App\Enums\OrderArrangementStepsEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\Package;
use App\Enums\PackageStatusEnum;
use App\Models\Shop;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackageService
{
    private  ShopAdapterContract $shopService;
    private Shop $shop;

    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;

        $this->shopService = $shop->shop_type->service()::make($shop);

        return $this;
    }

    public function getPackages(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        //special case pagination orders and package limit only to 25
        return Package::with('order')
            ->search($search)
            ->paginate(perPage: $perPage <= 25 ? $perPage : 25)
            ->withQueryString();
    }

    public function getPackage(Package $package)
    {
        return $package->loadMissing('order');
    }

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
            'package_status' => PackageStatusEnum::fromShopee($package->externalPackageStatus)->value,
        ])->save();

        return true;
    }

    /**
     * syncing an order's parcels from the marketplace.
     *
     * @param Closure $beforeSync lets caller decide what need to done before syncing the package
     * Example  delete the existing order packages before syncing fn() => $order->packages()->delete()`).
     *
     */
    public function syncPackage(Order $order, ?SyncPackageRequestData $package = null, ?Closure $beforeSync = null): void
    {
        $packages = $this->shopService
            ->getOrderPackages(new GetOrderRequestData([$order->external_order_id]));

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
                        'details' => [
                            "raw_data" =>  $parcel->details,
                            'doc_info' => [],
                            'tracking_number' => ''
                        ],
                    ]
                );
            }
        });
    }

    public function processShipment(Order $order): Collection
    {
        if (! $order->packages()->exists()) {
            $this->syncPackage(order: $order);
        }

        return $order->packages;
    }

    /**
     * Re-fetch a parcel's order from the marketplace and rebuild its packages,
     * then return the refreshed package.
     */
    public function syncFromMarketplace(Package $package): Package
    {
        $this->setShop($package->order->shop);

        $this->syncPackage($package->order);

        return $package->refresh();
    }

    public function getShippingOptions(Package $package): ShippingOptionsResponse
    {
        return $this->shopService->getShippingOptions($package);
    }

    public function shipPackage(ShipPackageRequestData $data): bool
    {
        $shipped = $this->shopService->shipPackage($data);

        if ($shipped) {
            $order = $data->package->order;
            $order->order_status = OrderStatusEnum::PROCESSED;
            $order->save();
        }

        return $shipped;
    }

    public function arrangePackageShipment(
        OrderArrangementStepsEnum $step,
        ?Order $order = null,
        ?Package $package = null,
        ?ShipPackageRequestData $shipData = null,
    ): mixed {
        OrderArrangementStepsEnum::ensureSupported($step, $this->shop->shop_type);

        return match ($step) {
            OrderArrangementStepsEnum::START_PROCESS => $this->processShipment($order),
            OrderArrangementStepsEnum::PICKUP => $this->getShippingOptions($package),
            OrderArrangementStepsEnum::SHIP => $this->shipPackage($shipData),
        };
    }

    public function getTrackingNumber(Package $package): ?string
    {
        $shippable = [PackageStatusEnum::SHIPPED->value, PackageStatusEnum::DELIVERED->value];

        if (! in_array($package->package_status, $shippable, true)) {
            throw new RuntimeException('only shipped or delivered packages can fetch a tracking number');
        }

        $trackingNumber = $this->shopService->getTrackingNumber($package);


        if ($trackingNumber === null) {
            return null;
        }

        $details = $package->details ?? [];
        $details['tracking_number'] = $trackingNumber;
        $package->details = $details;
        $package->save();

        return $trackingNumber;
    }
}
