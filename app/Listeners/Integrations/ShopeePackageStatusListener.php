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

class ShopeePackageStatusListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event->eventType !== ShopeeEventsEnum::PACKAGE_FULFILLMENT) {
            return;
        }

        $shop = Shop::query()
            ->getShop($event->payload['shop_id'], ShopsEnum::SHOPEE)
            ->first();

        if (!$shop) {
            return;
        };

        $externalOrderId = $event->payload['data']['ordersn'];

        $order = Order::query()
            ->getOrder($externalOrderId, $shop->id)
            ->first();

        if (!$order) {
            //if order not exist sync the order first, then reload it
            new OrderService()
                ->setShop($shop)
                ->syncShopOrder(new SyncOrderRequestData(externalOrderId: $externalOrderId));

            $order = Order::query()
                ->getOrder($externalOrderId, $shop->id)
                ->first();
        }

        if (!$order) {
            return;
        }

        $packageService = new PackageService()->setShop($shop);

        $packageRequestData = SyncPackageRequestData::fromShopee($event->payload);

        if (!$packageService->updatePackageStatus($packageRequestData)) {
            new PackageService()
                ->setShop($shop)
                ->syncPackage(
                    SyncPackageRequestData::fromShopee($event->payload),
                    $order,
                    fn() =>  $order->packages()->delete()
                );
        }
    }
}
