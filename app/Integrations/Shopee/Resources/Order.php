<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Requests\GetOrderDetails;
use App\Integrations\Shopee\Resource;

class Order extends Resource
{

    public function getOrderDetails(
        string $orderSnList,
        ?bool $requestOrderStatusPending = true,
        ?string $responseOptionalFiled = ''
    ) {
        return $this->connector->send(new GetOrderDetails(
            $orderSnList,
            $requestOrderStatusPending,
            $responseOptionalFiled
        ))->dtoOrFail();
    }
}
