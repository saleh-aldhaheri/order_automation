<?php

namespace App\Exceptions;

use App\Enums\ShopsEnum;
use Exception;

class ShopIntegrationException extends Exception
{
    private ShopsEnum $shop;

    public function __construct(ShopsEnum $shop, string $message)
    {
        $this->shop = $shop;
        parent::__construct("{$shop->name} Error: $message");
    }

    public function getShop(): ShopsEnum
    {
        return $this->shop;
    }
}
