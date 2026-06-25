<?php

namespace App\Listeners\Integrations;

use App\Data\Integrations\Requests\SyncOrderRequestData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Enums\ShopeeEventsEnum;
use App\Models\Shop;
use App\Services\OrderService;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopeeOrderStatusListener implements ShouldQueue
{
    public $tries = 5;

    public $backoff = [30, 60, 90];

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event->eventType !== ShopeeEventsEnum::ORDER_STATUS) {
            return;
        }

        $shop = Shop::query()
            ->getShop($event->payload['shop_id'], ShopsEnum::SHOPEE)
            ->first();

        if (!$shop) {
            return;
        }

        $syncOrder = SyncOrderRequestData::fromShopee($event->payload);

        // avoid race condition, expire after 10s
        Cache::lock('handle-orders-shopee:' . $syncOrder->externalOrderId, 10)
            ->block(5, function () use ($shop, $syncOrder) {
                (new OrderService())
                    ->setShop($shop)
                    ->syncShopOrder($syncOrder);
            });
    }

    public function failed(Throwable $exception)
    {
        Log::error('failed to sync the orders', ['error' => $exception->getMessage()]);

        Bugsnag::notifyException($exception, function ($report) use ($exception) {
            $report->setSeverity('error');
            $report->setMetaData([
                'order' => [
                    'provider' => 'shopee',
                    'action' => 'synching orders based on status',
                    'message' => $exception->getMessage(),
                ],
            ]);
        });
    }
}
