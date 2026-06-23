<?php

namespace App\Services;

use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Models\Order;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;

class OrderService
{
    private  ShopContract $shopService;
    private Shop $shop;

    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;

        $this->shopService = $shop->shop_type->service()::make($shop);

        return $this;
    }

    public function syncShopOrder(SyncOrderRequestData $syncOrderData): void
    {
        $order = Order::query()
            ->getOrder($syncOrderData->externalOrderId, $this->shop->id)
            ->first();

        if ($order) {
            $order->fill([
                'external_order_status' => $syncOrderData->externalOrderStatus,
                'order_status' => $syncOrderData->orderStatus->value
            ])->save();

            return;
        }

        //single order
        $orders = $this->shopService
            ->getOrder(new GetOrderRequestData([$syncOrderData->externalOrderId]));

        $orders->each(
            fn(OrderResponse $fetchedOrder) => Order::updateOrCreate(
                [
                    'external_order_id' => $fetchedOrder->externalOrderId,
                    'shop_id' => $fetchedOrder->shopId,
                ],
                [
                    'external_shop_id' => $fetchedOrder->externalShopId,
                    'shop_type' => $fetchedOrder->shopType->value,
                    'external_order_status' => $fetchedOrder->externalOrderStatus,
                    'order_status' => $fetchedOrder->orderStatus->value,
                    'details' => $fetchedOrder->details,
                ]
            )
        );
    }
}
