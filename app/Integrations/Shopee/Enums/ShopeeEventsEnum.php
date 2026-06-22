<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeeEventsEnum: int
{
    case ORDER_STATUS = 3;
    case ORDER_TRACKING = 4;
    case AUTH_CANCELED = 2;
    case AUTH_EXPIRY = 12;
}
