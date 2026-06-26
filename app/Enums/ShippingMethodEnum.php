<?php

namespace App\Enums;

/**
 * Vendor-neutral fulfilment method for a package.
 *
 * Each marketplace maps its own method vocabulary onto these in its service
 * (e.g. Shopee's get_shipping_parameter `info_needed` keys). A package may
 * support more than one method — the seller picks one.
 */
enum ShippingMethodEnum: string
{
    case PICKUP         = 'pickup';
    case DROPOFF        = 'dropoff';
    case NON_INTEGRATED = 'non_integrated';
}
