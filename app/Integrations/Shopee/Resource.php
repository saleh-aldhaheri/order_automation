<?php

namespace App\Integrations\Shopee;

use App\Integrations\Shopee\ShopeeClient;

class Resource
{
    public function __construct(
        protected ShopeeClient $connector
    ) {}
}
