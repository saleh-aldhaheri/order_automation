<?php

namespace App\Enums;

use App\Services\Integrations\Contracts\ShopContract;
use App\Services\Integrations\ShopeeService;
use RuntimeException;

enum ShopsEnum: string
{
    case SHOPEE = 'shopee';
    // case LAZADA = 'lazada';

    /**
     * The integration service that implements this marketplace.
     *
     * Single source of truth for vendor -> service resolution. Adding a new
     * marketplace means adding the case above and one arm here — nothing else
     * in the app needs to name a concrete service class.
     *
     * @return class-string<ShopContract>
     */
    public function service(): string
    {
        return match ($this) {
            self::SHOPEE => ShopeeService::class,
            // self::LAZADA => LazadaService::class,
            default =>  throw new RuntimeException("shop is not supported")
        };
    }
}
