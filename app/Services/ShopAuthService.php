<?php

namespace App\Services;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Enums\ShopsEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShopAuthService
{
    /** @var class-string<ShopAdapterContract> */
    private string $shopService;

    public function setShop(ShopsEnum $shopType): self
    {
        $this->shopService = $shopType->service();

        return $this;
    }

    public function constructUrl(): string
    {
        return $this->shopService::constructAuthorizationUrl();
    }

    /**
     * @return Collection<GetTokenResponseData>
     */
    public function callback(Request $request): Collection
    {
        return $this->shopService::handleCallback(new HandleCallbackRequest($request));
    }
}
