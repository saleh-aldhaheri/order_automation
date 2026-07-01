<?php

namespace App\Enums;

/**
 * Vendor-neutral list of inputs the seller must provide to ship a package.
 *
 * Drives the frontend: each method reports which of these it requires, so the
 * UI knows what to render (an address picker, a time-slot picker, a free-text
 * tracking number, etc.). Each marketplace maps its own field names onto these
 * (e.g. Shopee `address_id` -> PICKUP_ADDRESS, `tracking_no` -> TRACKING_NUMBER).
 */
enum ShippingInputEnum: string
{
    case PICKUP_ADDRESS = 'pickup_address';
    case PICKUP_TIME = 'pickup_time';
    case DROPOFF_BRANCH = 'dropoff_branch';
    case TRACKING_NUMBER = 'tracking_number';
    case SENDER_NAME = 'sender_name';
}
