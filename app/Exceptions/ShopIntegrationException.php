<?php

namespace App\Exceptions;

use App\Enums\ShopsEnum;
use Exception;

class ShopIntegrationException extends Exception
{
    private string $shop;

    public function __construct(?ShopsEnum $shop, string $message)
    {
        $this->shop = $shop?->value ?? 'shop';
        parent::__construct("{$this->shop} Error: $message");
    }

    public function getShop(): string
    {
        return $this->shop;
    }
}
