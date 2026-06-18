<?php

namespace App\Services\Integrations;

use App\Data\Integrations\Shopee\GetTokenData;
use App\Data\Integrations\Shopee\OrderStatusPushData;
use App\Enums\ProvidersEnum;
use App\Jobs\Integrations\RefreshProviderTokenJob;
use App\Integrations\Shopee\ShopeeConnector;
use App\Models\Provider;
use App\Services\Integrations\Contracts\ProviderContract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ShopeeService implements ProviderContract
{
    private ShopeeConnector $connector;

    private ?Provider $provider = null;

    private function __construct(
        public readonly int $partnerId,
        public readonly string $partnerKey,
        public readonly string $baseUrl,
        protected ?string $accessToken = null,
        public readonly ?string $providerId = null,
        public ?string $refreshToken = null,
    ) {
        $this->connector = new ShopeeConnector(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl,
            $this->accessToken,
            $this->providerId,
            $this->refreshToken,
        );
    }

    /**
     * Build a service. Pass a Provider (with tokens) for refresh/calls,
     * or none for the auth flow (authorization URL + callback).
     */
    public static function make(?Provider $provider = null): self
    {
        if ($provider && ! self::canCreate($provider)) {
            throw new Exception('Not a Shopee provider');
        }

        $config = $provider?->configuration;

        $service = new self(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: data_get($config, 'auth.access_token.token'),
            providerId: $provider?->provider_id,
            refreshToken: data_get($config, 'auth.refresh_token.token'),
        );

        $service->provider = $provider;

        return $service;
    }

    public static function canCreate(Provider $provider): bool
    {
        return $provider->provider_type === ProvidersEnum::SHOPEE->value;
    }

    private function validateState(string $state): bool
    {
        return hash_equals((string) session('shopee_oauth_state'), $state);
    }

    public function constructAuthorizationUrl(): string
    {
        $state = Str::random(40);

        session(['shopee_oauth_state' => $state]);

        $queryParameters = http_build_query([
            'partner_id'    => config('services.shopee.partner_id'),
            'auth_type'     => config('services.shopee.auth_type'),
            'redirect_uri'  => config('services.shopee.redirect_url'),
            'response_type' => 'code',
            'state'         => $state,
        ]);

        $baseUrl = rtrim(config('services.shopee.auth_base_url'), '/');

        return "{$baseUrl}/auth?{$queryParameters}";
    }

    public function handleCallback(Request $request): array
    {
        $code  = $request->query('code');
        $state = (string) $request->query('state');
        $providerId =  $request->query('shop_id');
        $idType = 'shop_id';

        if (!$providerId) {
            $providerId = $request->query('main_account_id');
            $idType = 'main_account_id';
        }

        if (! $code) {
            throw new Exception('Missing authorization code');
        }

        if (! $this->validateState($state)) {
            throw new Exception('state is wrong');
        }

        if (! $providerId) {
            throw new Exception('Unable to collect account id');
        }

        $data = $this->connector->authorization()->getToken($code, $providerId, $idType);

        $return  = [];

        if (!$data?->shopIdList || empty($data->shopIdList)) {
            $return[] = $this->createShope($data, $providerId);
        } else {
            $return = $this->createShops($data, $providerId);
        }

        return $return;
    }

    public function refreshToken(): Provider
    {
        try {
            $data = $this->connector->authorization()->refreshToken();

            $configuration = $this->provider->configuration;
            $configuration['auth']['access_token']['token']       = $data->accessToken;
            $configuration['auth']['access_token']['expired_in']  = now()->addSeconds($data->expireIn);
            $configuration['auth']['refresh_token']['token']      = $data->refreshToken;
            $configuration['auth']['refresh_token']['expired_in'] = now()->addDays(30);

            $this->provider->configuration = $configuration;
            $this->provider->save();
        } catch (Throwable $e) {
            Provider::query()->where('id', $this->provider->id)
                ->update([
                    'is_active' => false
                ]);
        }

        return $this->provider;
    }

    private function createShops(GetTokenData $data, int $mainAccountId)
    {

        $providers =  DB::transaction(function () use ($data, $mainAccountId) {
            return collect($data->shopIdList)->map(fn($providerId) => Provider::updateOrCreate(
                [
                    "provider_type" => ProvidersEnum::SHOPEE->value,
                    "provider_id" => $providerId
                ],
                [
                    'is_active' => true,
                    'configuration' => [
                        'main_account_id' => $mainAccountId,
                        'auth' => [
                            'access_token' => [
                                'token'      => $data->accessToken,
                                'expired_in' => now()->addSeconds($data->expireIn),
                            ],
                            'refresh_token' => [
                                'token'      => $data->refreshToken,
                                'expired_in' => now()->addDays(30),
                            ],
                        ],
                    ]
                ]
            ));
        });

        $providers->each(fn($provider) => RefreshProviderTokenJob::dispatch($provider->id));

        return $providers->all();
    }

    private function createShope(GetTokenData $data, int $providerId)
    {
        $configuration = [
            'auth' => [
                'access_token' => [
                    'token'      => $data->accessToken,
                    'expired_in' => now()->addSeconds($data->expireIn),
                ],
                'refresh_token' => [
                    'token'      => $data->refreshToken,
                    'expired_in' => now()->addDays(30),
                ],
            ],
        ];

        return Provider::updateOrCreate(
            [
                "provider_type" => ProvidersEnum::SHOPEE->value,
                "provider_id" => $providerId
            ],
            [
                'is_active' => true,
                'configuration' => $configuration
            ]
        );
    }

    public function requestOrders() {}

    public function processOrder() {}

    public function unProcessOrder() {}

    // order status event handling
    public function handleOrderStatus(OrderStatusPushData $data) {}
}
