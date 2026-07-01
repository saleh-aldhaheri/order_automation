<?php

namespace App\Enums;

use App\Adapters\ShopeeAdapter;
use App\Exceptions\ShopIntegrationException;
use App\Services\Integrations\Contracts\ShopContract;

enum ShopsEnum: string
{
    case SHOPEE = 'shopee';
    case LAZADA = 'lazada';

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
            self::SHOPEE => ShopeeAdapter::class,
            self::LAZADA => 'LazadaAdapter',
            default => throw new ShopIntegrationException(null, 'shop is not supported')
        };
    }
}
