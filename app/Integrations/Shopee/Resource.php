<?php

namespace App\Integrations\Shopee;

use App\Integrations\Shopee\ShopeeConnector;

class Resource
{
    public function __construct(
        protected ShopeeConnector $connector
    ) {}
}
