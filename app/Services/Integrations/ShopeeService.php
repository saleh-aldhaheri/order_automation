<?php

namespace App\Services\Integrations;

use App\Data\Integrations\Shopee\GetTokenData;
use App\Data\Integrations\Shopee\OrderStatusPushData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Requests\GetOrderDetails;
use App\Jobs\Integrations\RefreshShopTokenJob;
use App\Integrations\Shopee\ShopeeConnector;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ShopeeService implements ShopContract
{
    private ShopeeConnector $connector;

    private ?Shop $shop = null;

    private function __construct(
        public readonly int $partnerId,
        public readonly string $partnerKey,
        public readonly string $baseUrl,
        protected ?string $accessToken = null,
        public readonly ?string $externalShopId = null,
        public ?string $refreshToken = null,
    ) {
        $this->connector = new ShopeeConnector(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl,
            $this->accessToken,
            $this->externalShopId,
            $this->refreshToken,
        );
    }

    /**
     * Build a service. Pass a Shop (with tokens) for refresh/calls,
     * or none for the auth flow (authorization URL + callback).
     */
    public static function make(?Shop $shop = null): self
    {
        if ($shop && ! self::canCreate($shop)) {
            throw new Exception('Not a Shopee shop');
        }

        $config = $shop?->configuration;

        $service = new self(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: data_get($config, 'auth.access_token.token'),
            externalShopId: $shop?->external_shop_id,
            refreshToken: data_get($config, 'auth.refresh_token.token'),
        );

        $service->shop = $shop;

        return $service;
    }

    public static function canCreate(Shop $shop): bool
    {
        return $shop->shop_type === ShopsEnum::SHOPEE->value;
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
        $externalShopId =  $request->query('shop_id');
        $idType = 'shop_id';

        if (!$externalShopId) {
            $externalShopId = $request->query('main_account_id');
            $idType = 'main_account_id';
        }

        if (! $code) {
            throw new Exception('Missing authorization code');
        }

        if (! $this->validateState($state)) {
            throw new Exception('state is wrong');
        }

        if (! $externalShopId) {
            throw new Exception('Unable to collect account id');
        }

        $data = $this->connector->authorization()->getToken($code, $externalShopId, $idType);

        $return  = [];

        if (!$data?->shopIdList || empty($data->shopIdList)) {
            $return[] = $this->createShop($data, $externalShopId);
        } else {
            $return = $this->createShops($data, $externalShopId);
        }

        return $return;
    }

    public function refreshToken(): Shop
    {
        try {
            $data = $this->connector->authorization()->refreshToken();

            $configuration = $this->shop->configuration;
            $configuration['auth']['access_token']['token']       = $data->accessToken;
            $configuration['auth']['access_token']['expired_in']  = now()->addSeconds($data->expireIn);
            $configuration['auth']['refresh_token']['token']      = $data->refreshToken;
            $configuration['auth']['refresh_token']['expired_in'] = now()->addDays(30);

            $this->shop->configuration = $configuration;
            $this->shop->save();
        } catch (Throwable $e) {
            Shop::query()->where('id', $this->shop->id)
                ->update([
                    'is_active' => false
                ]);
        }

        return $this->shop;
    }

    private function createShops(GetTokenData $data, int $mainAccountId)
    {

        $shops =  DB::transaction(function () use ($data, $mainAccountId) {
            return collect($data->shopIdList)->map(fn($externalShopId) => Shop::updateOrCreate(
                [
                    "shop_type" => ShopsEnum::SHOPEE->value,
                    "external_shop_id" => $externalShopId
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

        $shops->each(fn($shop) => RefreshShopTokenJob::dispatch($shop->id));

        return $shops->all();
    }

    private function createShop(GetTokenData $data, int $externalShopId)
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

        return Shop::updateOrCreate(
            [
                "shop_type" => ShopsEnum::SHOPEE->value,
                "external_shop_id" => $externalShopId
            ],
            [
                'is_active' => true,
                'configuration' => $configuration
            ]
        );
    }

    public function getOrders(array $data)
    {
        $orderSn  = $data['order_sn'];
        $withPending = $data['with_pending'];
        return $this->connector
            ->order()
            ->getOrderDetails($orderSn, $withPending);
    }

    public function createOrder(GetOrderDetails $order) {}

    public function processOrder() {}

    public function unProcessOrder() {}

    // order status event handling
    public function handleOrderStatus(OrderStatusPushData $data) {}
}
