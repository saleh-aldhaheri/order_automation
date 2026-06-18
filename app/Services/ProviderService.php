<?php

namespace App\Services;

use App\Enums\ProvidersEnum;
use App\Models\Provider;
use App\Services\Integrations\Contracts\ProviderContract;
use App\Services\Integrations\ShopeeService;
use Illuminate\Http\Request;

class ProviderService
{
    private  ProviderContract $provider;

    /**
     * Select strategy by type (auth flow — no model yet).
     */
    public function setProvider(ProvidersEnum $providerType): self
    {
        $this->provider = $this->resolve($providerType);

        return $this;
    }

    /**
     * Select strategy from an existing provider model (has tokens).
     */
    public function setProviderFromModel(Provider $provider): self
    {
        $type = ProvidersEnum::from($provider->provider_type);
        $this->provider = $this->resolve($type, $provider);

        return $this;
    }

    private function resolve(ProvidersEnum $type, ?Provider $provider = null): ProviderContract
    {
        return match ($type) {
            ProvidersEnum::SHOPEE => ShopeeService::make($provider),
        };
    }

    public function url(): string
    {
        return $this->provider->constructAuthorizationUrl();
    }

    public function callback(Request $request): array
    {
        return $this->provider->handleCallback($request);
    }

    public function refresh(): Provider
    {
        return $this->provider->refreshToken();
    }
}
