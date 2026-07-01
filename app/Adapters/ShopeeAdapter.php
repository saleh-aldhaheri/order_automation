<?php

namespace App\Adapters;

use App\Adapters\Contracts\ShopAdapterContract;
use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Requests\ShipPackageRequestData;
use App\Data\Integrations\Responses\DocumentFileData;
use App\Data\Integrations\Responses\DocumentTypeOptionsResponse;
use App\Data\Integrations\Responses\DropoffBranchOption;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Data\Integrations\Responses\PackageResponse;
use App\Data\Integrations\Responses\PickupAddressOption;
use App\Data\Integrations\Responses\PickupTimeSlotOption;
use App\Data\Integrations\Responses\ShippingMethodOption;
use App\Data\Integrations\Responses\ShippingOptionsResponse;
use App\Enums\DocumentStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PackageStatusEnum;
use App\Enums\ShippingInputEnum;
use App\Enums\ShippingMethodEnum;
use App\Enums\ShopsEnum;
use App\Exceptions\ShopIntegrationException;
use App\Integrations\Shopee\Data\CreateShippingDocumentOrderData;
use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\PackageData;
use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Data\ShipOrderDropoffData;
use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use App\Integrations\Shopee\Data\ShippingAddressData;
use App\Integrations\Shopee\Data\ShippingBranchData;
use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Data\ShippingTimeSlotData;
use App\Integrations\Shopee\Enums\ShopeeDocumentStatus;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use App\Integrations\Shopee\ShopeeClient;
use App\Models\Package;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShopeeAdapter implements ShopAdapterContract
{
    private ShopeeClient $client;

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
        $this->client = new ShopeeClient(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl,
            $this->accessToken,
            $this->externalShopId,
            $this->refreshToken,
            function (RefreshAccessTokenData $refreshData) {
                $authConfiguration = $this->getConfiguration($refreshData);
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
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, 'Not a Shopee shop');
        }

        $config = $shop->auth_configuration;

        return new self(
            $shop,
            partnerId: (int) config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: data_get($config, 'auth.access_token.token'),
            externalShopId: $shop->external_shop_id,
            refreshToken: data_get($config, 'auth.refresh_token.token'),
        );
    }

    public static function canCreate(Shop $shop): bool
    {
        return $shop->shop_type === ShopsEnum::SHOPEE &&
            config('services.shopee.partner_id') &&
            config('services.shopee.partner_key') &&
            config('services.shopee.base_url') &&
            data_get($shop->auth_configuration, 'auth.access_token.token') &&
            $shop->external_shop_id &&
            data_get($shop->auth_configuration, 'auth.refresh_token.token');
    }

    public static function constructAuthorizationUrl(): string
    {
        $state = \Str::random(40);

        session(['shopee_oauth_state' => $state]);

        $queryParameters = http_build_query([
            'partner_id' => config('services.shopee.partner_id'),
            'auth_type' => config('services.shopee.auth_type'),
            'redirect_uri' => config('services.shopee.redirect_url'),
            'response_type' => 'code',
            'state' => $state,
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

        if (! $externalShopId) {
            $externalShopId = $data['main_account_id'];
            $idType = 'main_account_id';
        }

        if (! self::validateState($state)) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, 'state is wrong');
        }

        $token = (new ShopeeClient(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
        ))
            ->authorization()
            ->getAccessToken(
                $data['code'],
                $externalShopId,
                $idType
            );
        // getAccessToken() already screens Shopee's error envelope and throws, so
        // $token here is guaranteed to be a successful exchange with tokens set.

        $authConfiguration = [
            'auth' => [
                'access_token' => [
                    'token' => $token->accessToken,
                    'expired_in' => now()->addSeconds((int) $token->expireIn),
                ],
                'refresh_token' => [
                    'token' => $token->refreshToken,
                    'expired_in' => now()->addDays(30),
                ],
            ],
        ];

        $externalShopIds = $idType === 'main_account_id'
            ? ($token->shopIdList ?? [])
            : [$externalShopId];

        return collect($externalShopIds)->map(fn ($shopId) => new GetTokenResponseData(
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
     *         access_token: array{token: string, expired_in: Carbon},
     *         refresh_token: array{token: string, expired_in: Carbon}
     *     }
     * }
     */
    public function refreshAuthConfiguration(): array
    {
        $token = $this->client
            ->authorization()
            ->refreshAccessToken();

        if (! empty($token->error)) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE,
                'Shopee refresh access token request failed: '.($token->message ?: $token->error)
            );
        }

        if (empty($token->expireIn)) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, 'Shopee refresh access token response missing expire_in');
        }

        $authConfiguration = $this->getConfiguration($token);

        return $authConfiguration;
    }

    /**
     * Fetch order details from Shopee and translate them into the app's neutral
     * order DTOs. The integration layer returns Shopee's own DTOs; this service
     * is the seam that maps them into the application's language.
     *
     * @return Collection<int, OrderResponse>
     */
    public function getOrder(GetOrderRequestData $data): Collection
    {
        return $this->client
            ->order()
            ->getOrderDetail($data->ordersId ?? [])
            ->map(fn (GetOrderDetailsData $order) => $this->toOrderResponse($order));
    }

    /**
     * Fetch the parcels for the given order(s) and translate them into the app's
     * neutral package DTOs, flattened across every requested order.
     *
     * @return Collection<int, PackageResponse>
     */
    public function getOrderPackages(GetOrderRequestData $data): Collection
    {
        return $this->client
            ->order()
            ->getOrderDetail($data->ordersId ?? [])
            ->flatMap(fn (GetOrderDetailsData $order) => $this->toPackageResponses($order));
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
            ->map(fn (PackageData $package) => new PackageResponse(
                externalPackageId: (string) $package->packageNumber,
                externalOrderId: $order->orderSn,
                shopType: ShopsEnum::SHOPEE,
                externalPackageStatus: (string) $package->logisticsStatus,
                details: $package->toArray(),
                packageStatus: PackageStatusEnum::fromShopee((string) $package->logisticsStatus)->value,
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
            throw new ShopIntegrationException(ShopsEnum::SHOPEE,
                "Shopee shipping options unavailable for package status: {$package->external_package_status}"
            );
        }

        $params = $this->client
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
                    ->map(fn (ShippingAddressData $address) => new PickupAddressOption(
                        id: (string) $address->addressId,
                        address: $address->address,
                        region: $address->region,
                        state: $address->state,
                        city: $address->city,
                        zipcode: $address->zipcode,
                        timeSlots: collect($address->timeSlotList ?? [])
                            ->map(fn (ShippingTimeSlotData $slot) => new PickupTimeSlotOption(
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
                    ->map(fn (ShippingBranchData $branch) => new DropoffBranchOption(
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
            ->map(fn (string $field) => match ($field) {
                'address_id' => ShippingInputEnum::PICKUP_ADDRESS,
                'pickup_time_id' => ShippingInputEnum::PICKUP_TIME,
                'branch_id' => ShippingInputEnum::DROPOFF_BRANCH,
                'tracking_no', 'tracking_number' => ShippingInputEnum::TRACKING_NUMBER,
                'sender_real_name' => ShippingInputEnum::SENDER_NAME,
                default => null,
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

        return $this->client
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
        $tracking = $this->client
            ->logistic()
            ->getTrackingNumber(
                $package->external_order_id,
                $package->external_package_id,
            );

        return $tracking->trackingNumber ?: null;
    }

    public function getDocumentType(Package $package): DocumentTypeOptionsResponse
    {
        // We send a single order, so we only care about the first result entry.
        $result = $this->client->logistic()->getShippingDocumentParameter([
            new ShippingDocumentOrderData(
                $package->external_order_id,
                $package->external_package_id,
            ),
        ])->first();

        if ($result === null) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, 'Shopee returned no shipping-document parameters for this package');
        }

        if ($result->failError) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, "Shopee shipping-document parameter failed: {$result->failError} {$result->failMessage}");
        }

        return new DocumentTypeOptionsResponse(
            externalOrderId: $result->orderSn,
            externalPackageId: $result->packageNumber,
            shopType: ShopsEnum::SHOPEE,
            suggestedType: $result->suggestShippingDocumentType,
            selectableTypes: $result->selectableShippingDocumentType ?? [],
        );
    }

    public function createDocument(Package $package, string $documentType): bool
    {
        // Use the seller's selected type, not details.doc_info.type — that is only
        // persisted by the application *after* this call returns successfully.
        $result = $this->client->logistic()->createShippingDocument([
            new CreateShippingDocumentOrderData(
                $package->external_order_id,
                $package->external_package_id,
                data_get($package->details, 'tracking_number'),
                ShopeeShippingDocumentTypeEnum::from($documentType),
            ),
        ])->first();

        if ($result?->failError) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE, "Shopee create-shipping-document failed: {$result->failError} {$result->failMessage}");
        }

        return $result !== null;
    }

    public function checkDocumentStatus(Package $package): DocumentStatusEnum
    {
        $result = $this->client->logistic()->getShippingDocumentResult([
            new GetShippingDocumentResultOrderData(
                $package->external_order_id,
                $package->external_package_id,
                ShopeeShippingDocumentTypeEnum::from(data_get($package->details, 'doc_info.type')),
            ),
        ])->first();

        if ($result === null || $result->failError) {
            throw new ShopIntegrationException(ShopsEnum::SHOPEE,
                'Shopee shipping-document result failed: '
                .($result->failError ?? 'no result returned').' '.($result->failMessage ?? '')
            );
        }

        // Shopee reports the status uppercase (READY / PROCESSING / FAILED);
        return DocumentStatusEnum::fromShopee(
            ShopeeDocumentStatus::from(strtolower((string) $result->status))
        );
    }

    public function downloadDocument(Package $package): DocumentFileData
    {
        $content = $this->client->logistic()->downloadShippingDocument(
            [new ShippingDocumentOrderData(
                $package->external_order_id,
                $package->external_package_id,
            )],
            ShopeeShippingDocumentTypeEnum::from(data_get($package->details, 'doc_info.type')),
        );

        return new DocumentFileData(
            content: $content,
            mimeType: 'application/pdf',
            fileName: 'waybill-'.$package->external_package_id.'.pdf',
        );
    }

    /**
     * @return array|mixed
     */
    public function getConfiguration(RefreshAccessTokenData $refreshData): mixed
    {
        $authConfiguration = $this->shop->auth_configuration;
        $authConfiguration['auth']['access_token']['token'] = $refreshData->accessToken;
        $authConfiguration['auth']['access_token']['expired_in'] = now()->addSeconds($refreshData->expireIn);
        $authConfiguration['auth']['refresh_token']['token'] = $refreshData->refreshToken;
        $authConfiguration['auth']['refresh_token']['expired_in'] = now()->addDays(30);

        return $authConfiguration;
    }
}
