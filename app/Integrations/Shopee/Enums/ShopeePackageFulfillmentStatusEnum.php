<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeePackageFulfillmentStatusEnum: string
{
    public const LOGISTICS_NOT_START        = 'LOGISTICS_NOT_START';
    public const LOGISTICS_READY            = 'LOGISTICS_READY';
    public const LOGISTICS_REQUEST_CREATED  = 'LOGISTICS_REQUEST_CREATED';
    public const LOGISTICS_PICKUP_DONE      = 'LOGISTICS_PICKUP_DONE';
    public const LOGISTICS_DELIVERY_DONE    = 'LOGISTICS_DELIVERY_DONE';
    public const LOGISTICS_PICKUP_RETRY     = 'LOGISTICS_PICKUP_RETRY';
    public const LOGISTICS_INVALID          = 'LOGISTICS_INVALID';
    public const LOGISTICS_REQUEST_CANCELED = 'LOGISTICS_REQUEST_CANCELED';
    public const LOGISTICS_PICKUP_FAILED    = 'LOGISTICS_PICKUP_FAILED';
    public const LOGISTICS_DELIVERY_FAILED  = 'LOGISTICS_DELIVERY_FAILED';
    public const LOGISTICS_LOST             = 'LOGISTICS_LOST';

    public static function label(string $status): string
    {
        switch ($status) {
            case self::LOGISTICS_NOT_START:
                return 'Not Started';
            case self::LOGISTICS_READY:
                return 'Ready';
            case self::LOGISTICS_REQUEST_CREATED:
                return 'Request Created';
            case self::LOGISTICS_PICKUP_DONE:
                return 'Pickup Done';
            case self::LOGISTICS_DELIVERY_DONE:
                return 'Delivery Done';
            case self::LOGISTICS_PICKUP_RETRY:
                return 'Pickup Retry';
            case self::LOGISTICS_INVALID:
                return 'Invalid';
            case self::LOGISTICS_REQUEST_CANCELED:
                return 'Request Canceled';
            case self::LOGISTICS_PICKUP_FAILED:
                return 'Pickup Failed';
            case self::LOGISTICS_DELIVERY_FAILED:
                return 'Delivery Failed';
            case self::LOGISTICS_LOST:
                return 'Lost';
            default:
                return $status;
        }
    }

    public static function description(string $status): string
    {
        switch ($status) {
            case self::LOGISTICS_NOT_START:
                return 'Initial status, package not ready for fulfillment.';
            case self::LOGISTICS_READY:
                return 'Package ready for fulfillment from payment perspective (non-COD: paid; COD: passed screening). Call get_shipping_parameter here.';
            case self::LOGISTICS_REQUEST_CREATED:
                return 'Package arranged shipment.';
            case self::LOGISTICS_PICKUP_DONE:
                return 'Package handed over to 3PL. Cannot create the waybill after this.';
            case self::LOGISTICS_DELIVERY_DONE:
                return 'Package successfully delivered.';
            case self::LOGISTICS_PICKUP_RETRY:
                return 'Package pending 3PL retry pickup. Use update_shipping_order here.';
            case self::LOGISTICS_INVALID:
                return 'Order cancelled when package at LOGISTICS_READY.';
            case self::LOGISTICS_REQUEST_CANCELED:
                return 'Order cancelled when package at LOGISTICS_REQUEST_CREATED.';
            case self::LOGISTICS_PICKUP_FAILED:
                return 'Order cancelled by 3PL due to failed pickup, or picked up but unable to proceed with delivery.';
            case self::LOGISTICS_DELIVERY_FAILED:
                return 'Order cancelled due to 3PL delivery failure.';
            case self::LOGISTICS_LOST:
                return 'Order cancelled due to 3PL losing the package.';
            default:
                return '';
        }
    }
}
