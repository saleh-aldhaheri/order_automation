<?php

namespace App\Services;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    private  ShopAdapterContract $shopService;
    private Shop $shop;

    public function getOrders(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        //special case pagination orders and package limit only to 25
        return Order::with('packages')
            ->search($search)
            ->paginate(perPage: $perPage <= 25 ? $perPage : 25)
            ->withQueryString();
    }

    public function getOrder(Order $order)
    {
        return $order->loadMissing(['packages', 'shop']);
    }

    /**
     * Re-fetch the order from its marketplace and stamp the latest status locally.
     */
    public function syncOrderStatus(Order $order): Order
    {
        $this->setShop($order->shop);

        $fetched = $this->shopService
            ->getOrder(new GetOrderRequestData([$order->external_order_id]))
            ->first();

        if ($fetched) {
            $order->update([
                'external_order_status' => $fetched->externalOrderStatus,
                'order_status' => $fetched->orderStatus->value,
                'details' => $fetched->details,
            ]);
        }

        return $order;
    }

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
            // A status-only push (e.g. the package flow) carries just the order id,
            // so only stamp the status fields when the push actually included one.
            if ($syncOrderData->orderStatus) {
                $this->syncOrderStatus($order);
            }

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
