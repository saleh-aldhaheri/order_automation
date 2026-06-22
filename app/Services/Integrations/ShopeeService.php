<?php

namespace App\Services\Integrations;

use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Responses\GetOrderResponseData;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Enums\OrderStatusEnum;
use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Integrations\Shopee\ShopeeClient;
use App\Models\Shop;
use App\Services\Integrations\Contracts\ShopContract;
use Illuminate\Support\Collection;
use RuntimeException;

class ShopeeService implements ShopContract
{
    private ShopeeClient $connector;

    /**
     * Create a new class instance.
     */
    private function __construct(
        private Shop $shop,
        public readonly int $partnerId,
        public readonly string $partnerKey,
        public readonly string $baseUrl,
        protected ?string $accessToken = null,
        public readonly ?string $externalShopId = null,
        public ?string $refreshToken = null,
    ) {
        $this->connector = new ShopeeClient(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl,
            $this->accessToken,
            $this->externalShopId,
            $this->refreshToken,
            function (RefreshAccessTokenData $refreshData) {
                $authConfiguration = $this->shop->auth_configuration;
                $authConfiguration['auth']['access_token']['token']       = $refreshData->accessToken;
                $authConfiguration['auth']['access_token']['expired_in']  = now()->addSeconds($refreshData->expireIn);
                $authConfiguration['auth']['refresh_token']['token']      = $refreshData->refreshToken;
                $authConfiguration['auth']['refresh_token']['expired_in'] = now()->addDays(30);
                $this->shop->auth_configuration = $authConfiguration;
                $this->shop->save();
            }
        );
    }

    /**
     * Build a service. Pass a Shop (with tokens) for refresh/calls,
     * or none for the auth flow (authorization URL + callback).
     */
    public static function make(Shop $shop): self
    {
        if (! self::canCreate($shop)) {
            throw new RuntimeException('Not a Shopee shop');
        }

        $config = $shop->auth_configuration;

        return new self(
            $shop,
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: data_get($config, 'auth.access_token.token'),
            externalShopId: $shop->external_shop_id,
            refreshToken: data_get($config, 'auth.refresh_token.token'),
        );
    }

    public static function canCreate(Shop $shop): bool
    {
        return $shop->shop_type === ShopsEnum::SHOPEE;
    }

    public static function constructAuthorizationUrl(): string
    {
        $state = \Str::random(40);

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

    private static function validateState(string $state): bool
    {
        return hash_equals((string) session('shopee_oauth_state'), $state);
    }

    /**
     * Exchange the OAuth callback `code` for access/refresh tokens.
     *
     * A `main_account_id` grant covers several shops, so the token DTO's
     * `shopIdList` is fanned out into one {@see GetTokenResponseData} per shop.
     * A `shop_id` grant resolves to a single shop, returned as a one-element Collection.
     * Either way the result is a flat Collection of token DTOs.
     *
     * @return Collection<GetTokenResponseData>
     */
    public static function handleCallback(HandleCallbackRequest $data): Collection
    {
        $data = $data->toShopee();

        $state = $data['state'];
        $externalShopId = $data['shop_id'];
        $idType = 'shop_id';

        if (!$externalShopId) {
            $externalShopId = $data['main_account_id'];
            $idType = 'main_account_id';
        }

        if (! self::validateState($state)) {
            throw new RuntimeException('state is wrong');
        }

        $token = new ShopeeClient(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
        )
            ->authorization()
            ->getAccessToken(
                $data['code'],
                $externalShopId,
                $idType
            );

        if (! empty($token->error)) {
            throw new RuntimeException(
                'Shopee get access token request failed: ' . ($token->message ?: $token->error)
            );
        }

        $authConfiguration = [
            'auth' => [
                'access_token' => [
                    'token'      => $token->accessToken,
                    'expired_in' => now()->addSeconds((int) $token->expireIn),
                ],
                'refresh_token' => [
                    'token'      => $token->refreshToken,
                    'expired_in' => now()->addDays(30),
                ],
            ],
        ];

        $externalShopIds = $idType === 'main_account_id'
            ? ($token->shopIdList ?? [])
            : [$externalShopId];

        return collect($externalShopIds)->map(fn($shopId) => new GetTokenResponseData(
            externalShopId: (string) $shopId,
            shopType: ShopsEnum::SHOPEE,
            authConfiguration: $authConfiguration,
            isActive: true,
        ));
    }


    /**
     * Refresh the shop's tokens and return the updated `auth_configuration`
     * payload. NOTE: this builds the array but does NOT persist it — the caller
     * is responsible for saving it back onto the Shop.
     *
     * @return array{
     *     auth: array{
     *         access_token: array{token: string, expired_in: \Illuminate\Support\Carbon},
     *         refresh_token: array{token: string, expired_in: \Illuminate\Support\Carbon}
     *     }
     * }
     */
    public function refreshAuthConfiguration(): array
    {
        $token = $this->connector
            ->authorization()
            ->refreshAccessToken();

        if (! empty($token->error)) {
            throw new RuntimeException(
                'Shopee refresh access token request failed: ' . ($token->message ?: $token->error)
            );
        }

        if (empty($token->expireIn)) {
            throw new RuntimeException('Shopee refresh access token response missing expire_in');
        }

        $authConfiguration = $this->shop->auth_configuration;

        $authConfiguration['auth']['access_token']['token']       = $token->accessToken;
        $authConfiguration['auth']['access_token']['expired_in']  = now()->addSeconds($token->expireIn);
        $authConfiguration['auth']['refresh_token']['token']      = $token->refreshToken;
        $authConfiguration['auth']['refresh_token']['expired_in'] = now()->addDays(30);

        return $authConfiguration;
    }

    /**
     * Fetch order details from Shopee and translate them into the app's neutral
     * order DTOs. The integration layer returns Shopee's own DTOs; this service
     * is the seam that maps them into the application's language.
     *
     * @return \Illuminate\Support\Collection<int, GetOrderResponseData>
     */
    public function getOrder(GetOrderRequestData $data): Collection
    {
        return $this->connector
            ->order()
            ->getOrderDetails($data->ordersId ?? [])
            ->map(fn(GetOrderDetailsData $order) => $this->toOrderResponse($order));
    }

    /**
     * Translate a single Shopee order DTO into the app's neutral order DTO,
     * stamping our internal shop id and mapping Shopee's status to the local one.
     */
    private function toOrderResponse(GetOrderDetailsData $order): GetOrderResponseData
    {
        $status = ShopeeOrderStatusEnum::from($order->orderStatus);

        return new GetOrderResponseData(
            externalOrderId: $order->orderSn,
            shopId: (string) $this->shop->id,
            shopType: ShopsEnum::SHOPEE,
            externalOrderStatus: $status->value,
            orderStatus: OrderStatusEnum::fromShopee($status),
            details: $order->toArray(),
        );
    }
}
