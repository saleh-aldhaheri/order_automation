<?php

namespace App\Services\Integrations\Contracts;

use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Responses\GetOrderResponseData;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Models\Shop;
use Illuminate\Support\Collection;

interface ShopContract
{
    /**
     * Factory for a shop-bound service instance.
     *
     * Required so the marketplace can be resolved generically via
     * {@see \App\Enums\ShopsEnum::service()} as `$type->service()::make($shop)`.
     */
    public static function make(Shop $shop): self;

    public static function constructAuthorizationUrl(): string;

    /**
     * Exchange the OAuth callback for tokens (auth flow — no Shop yet).
     *
     * @return Collection<GetTokenResponseData>
     */
    public static function handleCallback(HandleCallbackRequest $callbackRequest): Collection;

    /**
     * Refresh the shop's tokens and return the updated auth configuration.
     * Each marketplace has its own authentication scheme.
     *
     * @return array<string, mixed>
     */
    public function refreshAuthConfiguration(): array;

    /**
     * @return \Illuminate\Support\Collection<int, GetOrderResponseData>
     */
    public function getOrder(GetOrderRequestData $data): Collection;
}
