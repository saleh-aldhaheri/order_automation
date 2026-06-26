<?php

namespace App\Adapters;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Requests\ShipPackageRequestData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Data\Integrations\Responses\PackageResponse;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Data\Integrations\Responses\DropoffBranchOption;
use App\Data\Integrations\Responses\PickupAddressOption;
use App\Data\Integrations\Responses\PickupTimeSlotOption;
use App\Data\Integrations\Responses\ShippingMethodOption;
use App\Data\Integrations\Responses\ShippingOptionsResponse;
use App\Enums\OrderStatusEnum;
use App\Enums\PackageStatusEnum;
use App\Enums\ShippingInputEnum;
use App\Enums\ShippingMethodEnum;
use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Data\PackageData;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\ShippingAddressData;
use App\Integrations\Shopee\Data\ShippingBranchData;
use App\Integrations\Shopee\Data\ShippingTimeSlotData;
use App\Integrations\Shopee\Data\ShipOrderDropoffData;
use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;
use App\Integrations\Shopee\ShopeeClient;
use App\Models\Package;
use App\Models\Shop;
use Illuminate\Support\Collection;
use RuntimeException;

class ShopeeAdapter implements ShopAdapterContract
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
     * @return \Illuminate\Support\Collection<int, OrderResponse>
     */
    public function getOrder(GetOrderRequestData $data): Collection
    {
        return $this->connector
            ->order()
            ->getOrderDetail($data->ordersId ?? [])
            ->map(fn(GetOrderDetailsData $order) => $this->toOrderResponse($order));
    }

    /**
     * Fetch the parcels for the given order(s) and translate them into the app's
     * neutral package DTOs, flattened across every requested order.
     *
     * @return \Illuminate\Support\Collection<int, PackageResponse>
     */
    public function getOrderPackages(GetOrderRequestData $data): Collection
    {
        return $this->connector
            ->order()
            ->getOrderDetail($data->ordersId ?? [])
            ->flatMap(fn(GetOrderDetailsData $order) => $this->toPackageResponses($order));
    }

    /**
     * Translate a single Shopee order DTO into the app's neutral order DTO,
     * stamping our internal shop id and mapping Shopee's status to the local one.
     */
    private function toOrderResponse(GetOrderDetailsData $order): OrderResponse
    {
        $status = ShopeeOrderStatusEnum::from($order->orderStatus);

        return new OrderResponse(
            externalOrderId: $order->orderSn,
            shopId: (string) $this->shop->id,
            externalShopId: (string) $this->externalShopId,
            shopType: ShopsEnum::SHOPEE,
            externalOrderStatus: $status->value,
            orderStatus: OrderStatusEnum::fromShopee($status),
            details: $order->toArray(),
        );
    }

    /**
     * Translate a Shopee order's parcels into the app's neutral package DTOs.
     *
     * @return array<int, PackageResponse>
     */
    private function toPackageResponses(GetOrderDetailsData $order): array
    {
        return collect($order->packageList ?? [])
            ->map(fn(PackageData $package) => new PackageResponse(
                externalPackageId: (string) $package->packageNumber,
                externalOrderId: $order->orderSn,
                shopType: ShopsEnum::SHOPEE,
                externalPackageStatus: (string) $package->logisticsStatus,
                packageStatus: PackageStatusEnum::fromShopee((string) $package->logisticsStatus)->value,
                details: $package->toArray(),
            ))
            ->all();
    }

    /**
     * Fetch a package's shipping options and translate Shopee's
     * get_shipping_parameter payload into the app's neutral DTO.
     */
    public function getShippingOptions(Package $package): ShippingOptionsResponse
    {
        // Shopee only exposes shipping parameters while the parcel can still be
        // arranged; this eligibility rule is Shopee-specific, so it lives here.
        $shippableStatuses = [
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_RETRY,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CREATED,
        ];

        if (! in_array($package->external_package_status, $shippableStatuses, true)) {
            throw new RuntimeException(
                "Shopee shipping options unavailable for package status: {$package->external_package_status}"
            );
        }

        $params = $this->connector
            ->logistic()
            ->getShippingParameter($package->external_order_id, $package->external_package_id);

        return $this->toShippingOptionsResponse($package, $params);
    }

    /**
     * Translate Shopee's get_shipping_parameter `response` into the neutral
     * {@see ShippingOptionsResponse}. Only the methods Shopee marks supported in
     * `info_needed` are emitted; the seller picks one of them.
     */
    private function toShippingOptionsResponse(
        Package $package,
        GetShippingParameterData $params
    ): ShippingOptionsResponse {
        $info = $params->infoNeeded;
        $methods = [];

        if (! empty($info?->pickup)) {
            $methods[] = new ShippingMethodOption(
                method: ShippingMethodEnum::PICKUP,
                requiredInputs: $this->mapRequiredInputs($info->pickup),
                addresses: collect($params->pickup?->addressList ?? [])
                    ->map(fn(ShippingAddressData $address) => new PickupAddressOption(
                        id: (string) $address->addressId,
                        address: $address->address,
                        region: $address->region,
                        state: $address->state,
                        city: $address->city,
                        zipcode: $address->zipcode,
                        timeSlots: collect($address->timeSlotList ?? [])
                            ->map(fn(ShippingTimeSlotData $slot) => new PickupTimeSlotOption(
                                id: (string) $slot->pickupTimeId,
                                date: $slot->date,
                                label: $slot->timeText,
                                recommended: in_array('recommended', $slot->flags ?? [], true),
                            ))
                            ->all(),
                    ))
                    ->all(),
            );
        }

        if (! empty($info?->dropoff)) {
            $methods[] = new ShippingMethodOption(
                method: ShippingMethodEnum::DROPOFF,
                requiredInputs: $this->mapRequiredInputs($info->dropoff),
                branches: collect($params->dropoff?->branchList ?? [])
                    ->map(fn(ShippingBranchData $branch) => new DropoffBranchOption(
                        id: (string) $branch->branchId,
                        address: $branch->address,
                        region: $branch->region,
                        state: $branch->state,
                        city: $branch->city,
                        zipcode: $branch->zipcode,
                    ))
                    ->all(),
            );
        }

        if (! empty($info?->nonIntegrated)) {
            $methods[] = new ShippingMethodOption(
                method: ShippingMethodEnum::NON_INTEGRATED,
                requiredInputs: $this->mapRequiredInputs($info->nonIntegrated),
            );
        }

        return new ShippingOptionsResponse(
            externalOrderId: $package->external_order_id,
            externalPackageId: $package->external_package_id,
            shopType: ShopsEnum::SHOPEE,
            methods: $methods,
        );
    }

    /**
     * Map Shopee's `info_needed` field names onto the app's neutral input enum.
     * Unknown fields are dropped.
     *
     * @param  array<int, string>  $fields
     * @return array<int, ShippingInputEnum>
     */
    private function mapRequiredInputs(array $fields): array
    {
        return collect($fields)
            ->map(fn(string $field) => match ($field) {
                'address_id'                       => ShippingInputEnum::PICKUP_ADDRESS,
                'pickup_time_id'                   => ShippingInputEnum::PICKUP_TIME,
                'branch_id'                        => ShippingInputEnum::DROPOFF_BRANCH,
                'tracking_no', 'tracking_number'   => ShippingInputEnum::TRACKING_NUMBER,
                'sender_real_name'                 => ShippingInputEnum::SENDER_NAME,
                default                            => null,
            })
            ->filter()
            ->values()
            ->all();
    }

    public function shipPackage(ShipPackageRequestData $data): bool
    {
        $package = $data->package;

        $pickup = $dropoff = $nonIntegrated = null;

        match ($data->method) {
            ShippingMethodEnum::PICKUP => $pickup = new ShipOrderPickupData(
                addressId: (int) $data->pickupAddressId,
                pickupTimeId: $data->pickupTimeId,
                trackingNumber: $data->trackingNumber,
            ),
            ShippingMethodEnum::DROPOFF => $dropoff = new ShipOrderDropoffData(
                branchId: $data->dropoffBranchId !== null ? (int) $data->dropoffBranchId : null,
                senderRealName: $data->senderName,
                trackingNumber: $data->trackingNumber,
            ),
            ShippingMethodEnum::NON_INTEGRATED => $nonIntegrated = new ShipOrderNonIntegratedData(
                trackingNumber: $data->trackingNumber,
            ),
        };

        return $this->connector
            ->logistic()
            ->shipOrder(
                $package->external_order_id,
                $package->external_package_id,
                $pickup,
                $dropoff,
                $nonIntegrated,
            );
    }

    public function getTrackingNumber(Package $package): ?string
    {
        $tracking = $this->connector
            ->logistic()
            ->getTrackingNumber(
                $package->external_order_id,
                $package->external_package_id,
            );

        return $tracking->trackingNumber ?: null;
    }
}
