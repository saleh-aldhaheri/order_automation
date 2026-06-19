<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Requests\GetOrderDetails;
use App\Integrations\Shopee\Resource;

class Order extends Resource
{

    public function getOrderDetails(
        array $orderSnList,
        ?bool $requestOrderStatusPending = true,
    ): mixed {
        return $this->connector->send(new GetOrderDetails(
            $orderSnList,
            $requestOrderStatusPending,
        ))->dtoOrFail();
    }
}
