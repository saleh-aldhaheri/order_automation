<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeeOrderStatusEnum: string
{
    case UNPAID = 'UNPAID';
    case READY_TO_SHIP = 'READY_TO_SHIP';
    case PROCESSED = 'PROCESSED';
    case SHIPPED = 'SHIPPED';
    case TO_CONFIRM_RECEIVE = 'TO_CONFIRM_RECEIVE';
    case COMPLETED = 'COMPLETED';
    case RETRY_SHIP = 'RETRY_SHIP';
    case IN_CANCEL = 'IN_CANCEL';
    case CANCELLED = 'CANCELLED';
    case TO_RETURN = 'TO_RETURN';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::READY_TO_SHIP => 'Ready to Ship',
            self::PROCESSED => 'Processed',
            self::SHIPPED => 'Shipped',
            self::TO_CONFIRM_RECEIVE => 'To Confirm Receive',
            self::COMPLETED => 'Completed',
            self::RETRY_SHIP => 'Retry Ship',
            self::IN_CANCEL => 'In Cancel',
            self::CANCELLED => 'Cancelled',
            self::TO_RETURN => 'To Return',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::UNPAID => 'Order is created, buyer has not paid yet.',
            self::READY_TO_SHIP => 'Seller can arrange shipment.',
            self::PROCESSED => 'Seller has arranged shipment online and got tracking number from 3PL.',
            self::SHIPPED => 'The parcel has been dropped to 3PL or picked up by 3PL.',
            self::TO_CONFIRM_RECEIVE => 'The order has been received by buyer.',
            self::COMPLETED => 'The order has been completed.',
            self::RETRY_SHIP => '3PL pickup parcel failed. Need to re-arrange shipment.',
            self::IN_CANCEL => 'The order\'s cancellation is under processing.',
            self::CANCELLED => 'The order has been cancelled.',
            self::TO_RETURN => 'The buyer requested to return the order and the return is processing.',
        };
    }
}
