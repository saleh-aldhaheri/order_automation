<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeeEventsEnum: int
{
    case SHOP_AUTH = 1;
    case AUTH_CANCELED = 2;
    case ORDER_STATUS = 3;
    case ORDER_TRACKING = 4;
    case AUTH_EXPIRY = 12;
    case SHIPPING_DOCUMENT_STATUS = 15;
    case PACKAGE_FULFILLMENT = 30;
}
