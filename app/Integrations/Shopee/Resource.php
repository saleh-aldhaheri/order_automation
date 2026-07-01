<?php

namespace App\Integrations\Shopee;

abstract class Resource
{
    public function __construct(
        protected ShopeeClient $connector
    ) {}
}
