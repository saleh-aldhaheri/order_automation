<?php

namespace App\Services;

use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Jobs\Integrations\RefreshShopTokenJob;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;
use Illuminate\Support\Collection;

class ShopService
{
    private  ShopContract $shopService;
    private Shop $shop;

    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;

        $this->shopService = $shop->shop_type->service()::make($shop);

        return $this;
    }

    /**
     * Create (or update) one or more shops from token DTOs, then queue a token
     * refresh for each so live access tokens are fetched right after creation.
     *
     * @param  Collection<GetTokenResponseData>  $shops
     */
    public static function createShops(Collection $shops): void
    {
        $createdShops = collect($shops)->map(fn(GetTokenResponseData $shop) => Shop::updateOrCreate(
            [
                'external_shop_id' => $shop->externalShopId,
                'shop_type' => $shop->shopType->value,
            ],
            [
                'auth_configuration' => $shop->authConfiguration,
                'is_active' => true,
            ]
        ));

        $createdShops->each(fn(Shop $shop) => RefreshShopTokenJob::dispatch($shop->id));
    }

    public function refreshAuthConfiguration(): Shop
    {
        $authConfig = $this->shopService->refreshAuthConfiguration();

        $this->shop->update([
            'auth_configuration' => $authConfig
        ]);

        $this->shop->refresh();

        return $this->shop;
    }
}
