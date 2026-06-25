<?php

namespace App\Listeners\Integrations;

use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Data\Integrations\Requests\SyncPackageRequestData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Enums\ShopeeEventsEnum;
use App\Models\Order;
use App\Models\Shop;
use App\Services\OrderService;
use App\Services\PackageService;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopeePackageStatusListener implements ShouldQueue
{
    public $tries = 5;

    public $backoff = [30, 60, 90];

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event->eventType !== ShopeeEventsEnum::PACKAGE_FULFILLMENT) {
            return;
        }

        $shop = Shop::query()->getShop($event->payload['shop_id'], ShopsEnum::SHOPEE)->first();
        if (!$shop) {
            return;
        }

        $externalOrderId = $event->payload['data']['ordersn'];
        $orderLockKey = 'handle-orders-shopee:' . $externalOrderId;

        $order = Order::query()->getOrder($externalOrderId, $shop->id)->first();

        if (!$order) {
            // avoid race conditions, lock automatically releases after 10s
            Cache::lock($orderLockKey, 10)->block(5, function () use ($shop, &$order, $externalOrderId) {
                // recheck inside lock - another job may have created it while we waited
                $order = Order::query()->getOrder($externalOrderId, $shop->id)->first();

                if ($order) {
                    return;
                }

                $syncOrder = new SyncOrderRequestData(externalOrderId: $externalOrderId);
                (new OrderService())
                    ->setShop($shop)
                    ->syncShopOrder($syncOrder);

                $order = Order::query()->getOrder($externalOrderId, $shop->id)->first();
            });
        }

        if (!$order) {
            return;
        }

        $packageService = (new PackageService())->setShop($shop);

        $packageRequestData = SyncPackageRequestData::fromShopee($event->payload);
        $packageLockKey = 'shopee-package:' . $packageRequestData->externalPackageId;

        // avoid race conditions, lock automatically releases after 10s
        Cache::lock($packageLockKey, 10)->block(
            5,
            function () use ($packageRequestData, &$order, $packageService) {
                if (!$packageService->updatePackageStatus($packageRequestData)) {
                    $packageService->syncPackage(
                        $packageRequestData,
                        $order,
                        fn() => $order->packages()->delete()
                    );
                }
            }
        );
    }

    public function failed(Throwable $exception)
    {
        Log::error('failed syncing packages', ['error' => $exception->getMessage()]);

        Bugsnag::notifyException($exception, function ($report) use ($exception) {
            $report->setSeverity('error');
            $report->setMetaData([
                'order' => [
                    'provider' => 'shopee',
                    'action' => 'synching packages based on status',
                    'message' => $exception->getMessage(),
                ],
            ]);
        });
    }
}
