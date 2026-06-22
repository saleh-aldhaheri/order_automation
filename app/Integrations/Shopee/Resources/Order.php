<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Requests\Order\GetOrderDetails;
use App\Integrations\Shopee\Resource;
use Illuminate\Support\Collection;

class Order extends Resource
{
    /**
     * @param  list<string>  $orderSnList
     * @return \Illuminate\Support\Collection<int, GetOrderDetailsData>
     */
    public function getOrderDetails(
        array $orderSnList,
        ?bool $requestOrderStatusPending = true,
    ): Collection {
        return $this->connector->send(new GetOrderDetails(
            $orderSnList,
            $requestOrderStatusPending,
        ))->dtoOrFail();
    }
}
