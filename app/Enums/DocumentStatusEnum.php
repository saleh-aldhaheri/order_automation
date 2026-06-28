<?php

namespace App\Enums;

use App\Integrations\Shopee\Enums\ShopeeDocumentStatus;

use RunTimeException;

enum DocumentStatusEnum: string
{
    case READY = 'ready';
    case UNREADY = 'unready';

    public static function fromShopee(ShopeeDocumentStatus $status)
    {
        return match ($status) {
            ShopeeDocumentStatus::PROCESSING,
            ShopeeDocumentStatus::FAILED => self::UNREADY,
            ShopeeDocumentStatus::READY => self::READY,
            default =>  throw new RunTimeException("unsupported status")
        };
    }
}
