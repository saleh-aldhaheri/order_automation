<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Data\GetOrderListData;
use App\Integrations\Shopee\Data\SplitOrderData;
use App\Integrations\Shopee\Data\SplitOrderPackageData;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Integrations\Shopee\Requests\Order\GetOrderDetail;
use App\Integrations\Shopee\Requests\Order\GetOrderList;
use App\Integrations\Shopee\Requests\Order\SplitOrder;
use App\Integrations\Shopee\Requests\Order\UnsplitOrder;
use App\Integrations\Shopee\Resource;
use Illuminate\Support\Collection;

class Order extends Resource
{
    /**
     * @param  list<string>  $orderSnList
     * @return \Illuminate\Support\Collection<int, GetOrderDetailsData>
     */
    public function getOrderDetail(
        array $orderSnList,
        ?bool $requestOrderStatusPending = true,
    ): Collection {
        return $this->connector->send(new GetOrderDetail(
            $orderSnList,
            $requestOrderStatusPending,
        ))->dtoOrFail();
    }

    public function getOrderList(
        string $timeRangeField,
        int $timeFrom,
        int $timeTo,
        int $pageSize = 50,
        ?string $cursor = null,
        ?ShopeeOrderStatusEnum $orderStatus = null,
        ?string $responseOptionalFields =  null,
        ?bool $requestOrderStatusPending = null,
        ?int $logisticsChannelId =  null,
    ): GetOrderListData {
        return $this->connector->send(new GetOrderList(
            $timeRangeField,
            $timeFrom,
            $timeTo,
            $pageSize,
            $cursor,
            $orderStatus,
            $responseOptionalFields,
            $requestOrderStatusPending,
            $logisticsChannelId
        ))->dtoOrFail();
    }

    /**
     * @param  array<int, SplitOrderPackageData>  $packageList
     */
    public function splitOrder(string $orderSn, array $packageList): SplitOrderData
    {
        return $this->connector->send(new SplitOrder($orderSn, $packageList))->dtoOrFail();
    }

    public function unsplitOrder(string $orderSn): bool
    {
        return $this->connector->send(new UnsplitOrder($orderSn))->dtoOrFail();
    }
}
