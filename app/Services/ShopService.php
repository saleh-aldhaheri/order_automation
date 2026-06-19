<?php

namespace App\Services;

use App\Enums\ShopsEnum;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;
use App\Services\Integrations\ShopeeService;
use Illuminate\Http\Request;

class ShopService
{
    private  ShopContract $shop;

    /**
     * Select strategy by type (auth flow — no model yet).
     */
    public function setShop(ShopsEnum $shopType): self
    {
        $this->shop = $this->resolve($shopType);

        return $this;
    }

    /**
     * Select strategy from an existing shop model (has tokens).
     */
    public function setShopFromModel(Shop $shop): self
    {
        $type = ShopsEnum::from($shop->shop_type);
        $this->shop = $this->resolve($type, $shop);

        return $this;
    }

    private function resolve(ShopsEnum $type, ?Shop $shop = null): ShopContract
    {
        return match ($type) {
            ShopsEnum::SHOPEE => ShopeeService::make($shop),
        };
    }

    public function url(): string
    {
        return $this->shop->constructAuthorizationUrl();
    }

    public function callback(Request $request): array
    {
        return $this->shop->handleCallback($request);
    }

    public function refresh(): Shop
    {
        return $this->shop->refreshToken();
    }
}
