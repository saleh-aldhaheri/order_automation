<?php

use App\Adapters\ShopeeAdapter;
use App\Data\Integrations\Requests\GetOrderRequestData;
use App\Data\Integrations\Requests\HandleCallbackRequest;
use App\Data\Integrations\Requests\ShipPackageRequestData;
use App\Data\Integrations\Responses\DocumentFileData;
use App\Data\Integrations\Responses\DocumentTypeOptionsResponse;
use App\Data\Integrations\Responses\GetTokenResponseData;
use App\Data\Integrations\Responses\OrderResponse;
use App\Data\Integrations\Responses\PackageResponse;
use App\Data\Integrations\Responses\ShippingOptionsResponse;
use App\Enums\DocumentStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PackageStatusEnum;
use App\Enums\ShippingInputEnum;
use App\Enums\ShippingMethodEnum;
use App\Enums\ShopsEnum;
use App\Exceptions\ShopIntegrationException;
use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Authorization\GetAccessToken;
use App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken;
use App\Integrations\Shopee\Requests\Logistics\Document\CreateShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\DownloadShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentParameter;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentResult;
use App\Integrations\Shopee\Requests\Logistics\GetShippingParameter;
use App\Integrations\Shopee\Requests\Logistics\GetTrackingNumber;
use App\Integrations\Shopee\Requests\Logistics\ShipOrder;
use App\Integrations\Shopee\Requests\Orders\GetOrderDetail;
use App\Models\Package;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(/**
 * @throws \Random\RandomException
 */ function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = (int) config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->refreshToken = bin2hex(random_bytes(16));
    $this->accessToken = bin2hex(random_bytes(16));
    $this->accessExpiresIn = time() + 60 * 60;  // for an hour
    $this->refreshExpiresIn = $this->accessExpiresIn * 30 * 24 * 60 * 60; // for 30 days

    $this->orderSn = bin2hex(random_bytes(16));
    $this->shopId = 10;
    $this->packageNumber = (string) random_int(1, 10);

    $this->shop = Shop::factory()->create([
        'shop_type' => ShopsEnum::SHOPEE,
        'external_shop_id' => $this->shopId,
        'is_active' => true,
        'auth_configuration' => [
            'auth' => [
                'access_token' => [
                    'token' => $this->accessToken,
                    'expired_at' => $this->accessExpiresIn,
                ],
                'refresh_token' => [
                    'token' => $this->refreshToken,
                    'expired_at' => $this->refreshExpiresIn,
                ],
            ],
        ],
    ]);
});

afterEach(function () {
    // handleCallback() builds its own connector internally, so it is mocked via a
    // global MockClient; tear it down so it never leaks into another test.
    MockClient::destroyGlobal();
});

describe('construction and validation', function () {
    it('builds a ShopeeAdapter for a fully configured Shopee shop', function () {
        $shopAdapter = ShopeeAdapter::make($this->shop);
        expect($shopAdapter)->toBeInstanceOf(ShopeeAdapter::class);
    });

    it('canCreate() is true only for an active Shopee shop that has both tokens', function ($shopType, $shopId, $isActive, $accessToken, $refreshToken, $expectedResult) {

        $shop = Shop::factory()->create([
            'shop_type' => $shopType,
            'external_shop_id' => $shopId,
            'is_active' => $isActive,
            'auth_configuration' => [
                'auth' => [
                    'access_token' => [
                        'token' => $accessToken,
                        'expired_at' => $this->accessExpiresIn,
                    ],
                    'refresh_token' => [
                        'token' => $refreshToken,
                        'expired_at' => $this->refreshExpiresIn,
                    ],
                ],
            ],
        ]);

        expect(ShopeeAdapter::canCreate($shop))->toBe($expectedResult);
    })->with([
        'shopee shop with both tokens' => [ShopsEnum::SHOPEE, 'shop_id', true, 'aaaaaa', 'rrrrrrrr', true],
        'missing refresh token' => [ShopsEnum::SHOPEE, 'shop_id', true, 'aaaaaa', null, false],
        'missing access token' => [ShopsEnum::SHOPEE, 'shop_id', true, null, 'rrrrrrrr', false],
        'missing both tokens' => [ShopsEnum::SHOPEE, 'shop_id', true, null, null, false],
        'not a shopee shop' => [ShopsEnum::LAZADA, 'shop_id', true, 'aaaaaa', 'rrrrrrrr', false],
    ]);

    it('canCreate() is false when any required Shopee integration config is missing', function () {
        collect([
            'partner_id' => fn () => config(['services.shopee.partner_id' => null]),
            'partner_key' => fn () => config(['services.shopee.partner_key' => null]),
            'base_url' => fn () => config(['services.shopee.base_url' => null]),
            'all' => fn () => config([
                'services.shopee.partner_id' => null,
                'services.shopee.partner_key' => null,
                'services.shopee.base_url' => null,
            ]),
        ])->each(function ($config) {
            $config();
            expect(ShopeeAdapter::canCreate($this->shop))->toBeFalse();
        });
    });

    it('make() throws a ShopIntegrationException when the shop is missing a token or is not Shopee', function ($shopType, $shopId, $isActive, $accessToken, $refreshToken) {
        $shop = Shop::factory()->create([
            'shop_type' => $shopType,
            'external_shop_id' => $shopId,
            'is_active' => $isActive,
            'auth_configuration' => [
                'auth' => [
                    'access_token' => [
                        'token' => $accessToken,
                        'expired_at' => $this->accessExpiresIn,
                    ],
                    'refresh_token' => [
                        'token' => $refreshToken,
                        'expired_at' => $this->refreshExpiresIn,
                    ],
                ],
            ],
        ]);

        ShopeeAdapter::make($shop);
    })
        ->throws(ShopIntegrationException::class)
        ->with([
            'missing refresh token' => [ShopsEnum::SHOPEE, 'shop_id', true, 'aaaaaa', null],
            'missing access token' => [ShopsEnum::SHOPEE, 'shop_id', true, null, 'rrrrrrrr'],
            'missing both tokens' => [ShopsEnum::SHOPEE, 'shop_id', true, null, null],
            'not a shopee shop' => [ShopsEnum::LAZADA, 'shop_id', true, 'aaaaaa', 'rrrrrrrr'],
        ]);

    it('make() throws a ShopIntegrationException when any required Shopee integration config is missing', function () {
        collect([
            'partner_id' => fn () => config(['services.shopee.partner_id' => null]),
            'partner_key' => fn () => config(['services.shopee.partner_key' => null]),
            'base_url' => fn () => config(['services.shopee.base_url' => null]),
            'all' => fn () => config([
                'services.shopee.partner_id' => null,
                'services.shopee.partner_key' => null,
                'services.shopee.base_url' => null,
            ]),
        ])->each(function ($config) {
            $config();
            try {
                ShopeeAdapter::make($this->shop);
                $this->fail('Expected ShopIntegrationException was not thrown');
            } catch (Throwable $e) {
                expect($e)->toBeInstanceOf(ShopIntegrationException::class);
            }
        });
    });
});

describe('refreshAuthConfiguration', function () {
    it('returns the rebuilt auth configuration with refreshed tokens and Carbon expiries', function () {

        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => $this->refreshToken,
                'access_token' => $this->accessToken,
                'expire_in' => $this->accessExpiresIn,
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $result = $adapter->refreshAuthConfiguration();

        expect($result)->toHaveKey('auth')
            ->and($result['auth'])->toHaveKey('access_token')
            ->and($result['auth'])->toHaveKey('refresh_token')
            ->and($result['auth']['access_token'])->toHaveKey('token')
            ->and($result['auth']['access_token'])->toHaveKey('expired_at')
            ->and($result['auth']['refresh_token'])->toHaveKey('token')
            ->and($result['auth']['refresh_token'])->toHaveKey('expired_at')
            ->and($result['auth']['access_token']['token'])->toBe($this->accessToken)
            ->and($result['auth']['refresh_token']['token'])->toBe($this->refreshToken)
            ->and($result['auth']['access_token']['expired_at'])->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($result['auth']['refresh_token']['expired_at'])->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('throws a ShopeeException when Shopee omits a required token field', function () {

        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => $this->refreshToken,
                'access_token' => $this->accessToken,
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->refreshAuthConfiguration();

    })->throws(ShopeeException::class);

    it('throws a ShopeeException when Shopee returns an error envelope', function () {

        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => $this->refreshToken,
                'access_token' => $this->accessToken,
                'expire_in' => $this->accessExpiresIn,
                'error' => 'this is shopee error',
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->refreshAuthConfiguration();

    })->throws(ShopeeException::class);
});

describe('getOrder', function () {
    it('maps Shopee order details into neutral OrderResponse DTOs', function () {
        $requestMock = new MockClient([
            GetOrderDetail::class => MockResponse::make([
                'response' => [
                    'order_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'region' => 'SG',
                            'currency' => 'SGD',
                            'cod' => false,
                            'order_status' => 'READY_TO_SHIP',
                            'create_time' => 1700000000,
                            'update_time' => 1700001000,
                            'days_to_ship' => 3,
                            'ship_by_date' => 1700300000,
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $orders = $adapter->getOrder(new GetOrderRequestData(['201218V2Y6E59M']));

        expect($orders)->toBeInstanceOf(Collection::class)
            ->and($orders)->toHaveCount(1)
            ->and($orders->first())->toBeInstanceOf(OrderResponse::class)
            ->and($orders[0]->externalOrderId)->toBe('201218V2Y6E59M')
            ->and($orders[0]->shopId)->toBe((string) $this->shop->id)
            ->and($orders[0]->externalShopId)->toBe((string) $this->shopId)
            ->and($orders[0]->shopType)->toBe(ShopsEnum::SHOPEE)
            ->and($orders[0]->externalOrderStatus)->toBe('READY_TO_SHIP')
            ->and($orders[0]->orderStatus)->toBe(OrderStatusEnum::UNPROCESSED)
            ->and($orders[0]->details)->toBeArray()
            ->and($orders[0]->details)->toHaveKey('order_sn');
    });

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $requestMock = new MockClient([
            GetOrderDetail::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getOrder(new GetOrderRequestData(['201218V2Y6E59M']));
    })->throws(ShopeeException::class);
});

describe('getOrderPackages', function () {
    it('flattens every order package into neutral PackageResponse DTOs', function () {
        $requestMock = new MockClient([
            GetOrderDetail::class => MockResponse::make([
                'response' => [
                    'order_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'region' => 'SG',
                            'currency' => 'SGD',
                            'cod' => false,
                            'order_status' => 'READY_TO_SHIP',
                            'create_time' => 1700000000,
                            'update_time' => 1700001000,
                            'days_to_ship' => 3,
                            'ship_by_date' => 1700300000,
                            'package_list' => [
                                [
                                    'package_number' => 'PKG-1',
                                    'logistics_status' => ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY,
                                ],
                                [
                                    'package_number' => 'PKG-2',
                                    'logistics_status' => ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $packages = $adapter->getOrderPackages(new GetOrderRequestData(['201218V2Y6E59M']));

        expect($packages)->toBeInstanceOf(Collection::class)
            ->and($packages)->toHaveCount(2)
            ->and($packages->first())->toBeInstanceOf(PackageResponse::class)
            ->and($packages[0]->externalPackageId)->toBe('PKG-1')
            ->and($packages[0]->externalOrderId)->toBe('201218V2Y6E59M')
            ->and($packages[0]->shopType)->toBe(ShopsEnum::SHOPEE)
            ->and($packages[0]->externalPackageStatus)->toBe(ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY)
            ->and($packages[0]->packageStatus)->toBe(PackageStatusEnum::READY->value)
            ->and($packages[0]->details)->toBeArray()
            ->and($packages[1]->externalPackageId)->toBe('PKG-2')
            ->and($packages[1]->packageStatus)->toBe(PackageStatusEnum::DELIVERED->value);
    });

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $requestMock = new MockClient([
            GetOrderDetail::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getOrderPackages(new GetOrderRequestData(['201218V2Y6E59M']));
    })->throws(ShopeeException::class);
});

describe('getShippingOptions', function () {
    it('maps the supported methods, addresses, time slots and branches into a neutral ShippingOptionsResponse', function () {
        $package = Package::factory()->create([
            'external_order_id' => '201218V2Y6E59M',
            'external_package_id' => 'PKG-1',
            'external_package_status' => ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY,
        ]);

        $requestMock = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'response' => [
                    'info_needed' => [
                        'pickup' => ['address_id', 'pickup_time_id'],
                        'dropoff' => ['branch_id'],
                        'non_integrated' => ['tracking_number'],
                    ],
                    'pickup' => [
                        'address_list' => [
                            [
                                'address_id' => 202,
                                'region' => 'SG',
                                'city' => 'Singapore',
                                'address' => '123 Main St',
                                'time_slot_list' => [
                                    [
                                        'pickup_time_id' => 'slot-1',
                                        'date' => 1700300000,
                                        'time_text' => '9am - 12pm',
                                        'flags' => ['recommended'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'dropoff' => [
                        'branch_list' => [
                            ['branch_id' => 101, 'region' => 'SG', 'city' => 'Singapore', 'address' => 'Branch 1'],
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $options = $adapter->getShippingOptions($package);

        expect($options)->toBeInstanceOf(ShippingOptionsResponse::class)
            ->and($options->externalOrderId)->toBe('201218V2Y6E59M')
            ->and($options->externalPackageId)->toBe('PKG-1')
            ->and($options->shopType)->toBe(ShopsEnum::SHOPEE)
            ->and($options->methods)->toHaveCount(3);

        $pickup = collect($options->methods)->firstWhere('method', ShippingMethodEnum::PICKUP);
        expect($pickup->requiredInputs)->toBe([ShippingInputEnum::PICKUP_ADDRESS, ShippingInputEnum::PICKUP_TIME])
            ->and($pickup->addresses)->toHaveCount(1)
            ->and($pickup->addresses[0]->id)->toBe('202')
            ->and($pickup->addresses[0]->address)->toBe('123 Main St')
            ->and($pickup->addresses[0]->timeSlots)->toHaveCount(1)
            ->and($pickup->addresses[0]->timeSlots[0]->id)->toBe('slot-1')
            ->and($pickup->addresses[0]->timeSlots[0]->recommended)->toBeTrue();

        $dropoff = collect($options->methods)->firstWhere('method', ShippingMethodEnum::DROPOFF);
        expect($dropoff->requiredInputs)->toBe([ShippingInputEnum::DROPOFF_BRANCH])
            ->and($dropoff->branches)->toHaveCount(1)
            ->and($dropoff->branches[0]->id)->toBe('101')
            ->and($dropoff->branches[0]->address)->toBe('Branch 1');

        $nonIntegrated = collect($options->methods)->firstWhere('method', ShippingMethodEnum::NON_INTEGRATED);
        expect($nonIntegrated->requiredInputs)->toBe([ShippingInputEnum::TRACKING_NUMBER]);
    });

    it('throws a ShopIntegrationException without calling Shopee when the package status is not shippable', function () {
        $package = Package::factory()->create([
            'external_package_status' => ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE,
        ]);

        $adapter = ShopeeAdapter::make($this->shop);

        $adapter->getShippingOptions($package);
    })->throws(ShopIntegrationException::class);

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $package = Package::factory()->create([
            'external_package_status' => ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY,
        ]);

        $requestMock = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getShippingOptions($package);
    })->throws(ShopeeException::class);
});

describe('shipPackage', function () {
    it('returns true when Shopee arranges the pickup shipment', function () {
        $package = Package::factory()->create([
            'external_order_id' => '201218V2Y6E59M',
            'external_package_id' => 'PKG-1',
        ]);

        $requestMock = new MockClient([
            ShipOrder::class => MockResponse::make(['response' => []]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $result = $adapter->shipPackage(new ShipPackageRequestData(
            package: $package,
            method: ShippingMethodEnum::PICKUP,
            pickupAddressId: '202',
            pickupTimeId: 'slot-1',
        ));

        expect($result)->toBeTrue();
    });

    it('throws a ShopeeException when Shopee rejects the shipment', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            ShipOrder::class => MockResponse::make([
                'error' => 'logistics.error_status',
                'message' => 'Order can not be shipped.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->shipPackage(new ShipPackageRequestData(
            package: $package,
            method: ShippingMethodEnum::NON_INTEGRATED,
            trackingNumber: 'TRK-123',
        ));
    })->throws(ShopeeException::class);
});

describe('getTrackingNumber', function () {
    it('returns the tracking number once the 3PL has assigned one', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'response' => ['tracking_number' => 'TRK-123'],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        expect($adapter->getTrackingNumber($package))->toBe('TRK-123');
    });

    it('returns null while the tracking number is still empty', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'response' => ['tracking_number' => ''],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        expect($adapter->getTrackingNumber($package))->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getTrackingNumber($package);
    })->throws(ShopeeException::class);
});

describe('getDocumentType', function () {
    it('maps the first parameter result into a neutral DocumentTypeOptionsResponse', function () {
        $package = Package::factory()->create([
            'external_order_id' => '201218V2Y6E59M',
            'external_package_id' => 'PKG-1',
        ]);

        $requestMock = new MockClient([
            GetShippingDocumentParameter::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'package_number' => 'PKG-1',
                            'suggest_shipping_document_type' => 'NORMAL_AIR_WAYBILL',
                            'selectable_shipping_document_type' => ['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL'],
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $response = $adapter->getDocumentType($package);

        expect($response)->toBeInstanceOf(DocumentTypeOptionsResponse::class)
            ->and($response->externalOrderId)->toBe('201218V2Y6E59M')
            ->and($response->externalPackageId)->toBe('PKG-1')
            ->and($response->shopType)->toBe(ShopsEnum::SHOPEE)
            ->and($response->suggestedType)->toBe('NORMAL_AIR_WAYBILL')
            ->and($response->selectableTypes)->toBe(['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL']);
    });

    it('throws a ShopIntegrationException when the result entry carries a fail error', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetShippingDocumentParameter::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        [
                            'order_sn' => $package->external_order_id,
                            'fail_error' => 'logistics.error_param',
                            'fail_message' => 'Order not found.',
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getDocumentType($package);
    })->throws(ShopIntegrationException::class);

    it('throws a ShopIntegrationException when Shopee returns no result entries', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetShippingDocumentParameter::class => MockResponse::make([
                'response' => ['result_list' => []],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getDocumentType($package);
    })->throws(ShopIntegrationException::class);

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $package = Package::factory()->create();

        $requestMock = new MockClient([
            GetShippingDocumentParameter::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->getDocumentType($package);
    })->throws(ShopeeException::class);
});

describe('createDocument', function () {
    it('returns true when Shopee accepts the document generation task', function () {
        $package = Package::factory()->create([
            'external_order_id' => '201218V2Y6E59M',
            'external_package_id' => 'PKG-1',
            'details' => ['tracking_number' => 'TRK-123', 'doc_info' => []],
        ]);

        $requestMock = new MockClient([
            CreateShippingDocument::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        ['order_sn' => '201218V2Y6E59M', 'package_number' => 'PKG-1'],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        expect($adapter->createDocument($package, ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL->value))->toBeTrue();
    });

    it('throws a ShopIntegrationException when the result entry carries a fail error', function () {
        $package = Package::factory()->create([
            'details' => ['tracking_number' => 'TRK-123', 'doc_info' => []],
        ]);

        $requestMock = new MockClient([
            CreateShippingDocument::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        [
                            'order_sn' => $package->external_order_id,
                            'fail_error' => 'logistics.error_status',
                            'fail_message' => 'Tracking number not ready.',
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->createDocument($package, ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL->value);
    })->throws(ShopIntegrationException::class);

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $package = Package::factory()->create([
            'details' => ['tracking_number' => 'TRK-123', 'doc_info' => []],
        ]);

        $requestMock = new MockClient([
            CreateShippingDocument::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->createDocument($package, ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL->value);
    })->throws(ShopeeException::class);
});

describe('checkDocumentStatus', function () {
    it('maps a READY Shopee document status to the neutral READY status', function () {
        $package = Package::factory()->create([
            'external_order_id' => '201218V2Y6E59M',
            'external_package_id' => 'PKG-1',
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $requestMock = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        ['order_sn' => '201218V2Y6E59M', 'package_number' => 'PKG-1', 'status' => 'READY'],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        expect($adapter->checkDocumentStatus($package))->toBe(DocumentStatusEnum::READY);
    });

    it('maps a PROCESSING Shopee document status to the neutral UNREADY status', function () {
        $package = Package::factory()->create([
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $requestMock = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        ['order_sn' => $package->external_order_id, 'status' => 'PROCESSING'],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        expect($adapter->checkDocumentStatus($package))->toBe(DocumentStatusEnum::UNREADY);
    });

    it('throws a ShopIntegrationException when the result entry carries a fail error', function () {
        $package = Package::factory()->create([
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $requestMock = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        [
                            'order_sn' => $package->external_order_id,
                            'status' => 'FAILED',
                            'fail_error' => 'logistics.error_status',
                            'fail_message' => 'Document generation failed.',
                        ],
                    ],
                ],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->checkDocumentStatus($package);
    })->throws(ShopIntegrationException::class);

    it('throws a ShopeeException when Shopee returns an error envelope', function () {
        $package = Package::factory()->create([
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $requestMock = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->checkDocumentStatus($package);
    })->throws(ShopeeException::class);
});

describe('downloadDocument', function () {
    it('wraps the raw waybill bytes in a neutral DocumentFileData with pdf metadata', function () {
        $package = Package::factory()->create([
            'external_package_id' => 'PKG-1',
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $file = '%PDF-1.4 fake waybill bytes';

        $requestMock = new MockClient([
            DownloadShippingDocument::class => MockResponse::make($file, 200),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $document = $adapter->downloadDocument($package);

        expect($document)->toBeInstanceOf(DocumentFileData::class)
            ->and($document->content)->toBe($file)
            ->and($document->mimeType)->toBe('application/pdf')
            ->and($document->fileName)->toBe('waybill-PKG-1.pdf');
    });

    it('throws a ShopeeException when Shopee returns a JSON error envelope instead of a file', function () {
        $package = Package::factory()->create([
            'details' => ['doc_info' => ['type' => 'NORMAL_AIR_WAYBILL']],
        ]);

        $requestMock = new MockClient([
            DownloadShippingDocument::class => MockResponse::make([
                'error' => 'logistics.error_status',
                'message' => 'Document is not ready to download.',
            ], 200),
        ], 200);

        $adapter = ShopeeAdapter::make($this->shop);
        $adapter->client->withMockClient($requestMock);

        $adapter->downloadDocument($package);
    })->throws(ShopeeException::class);
});

describe('constructAuthorizationUrl', function () {
    it('builds the Shopee OAuth url and stores the state in the session', function () {
        $url = ShopeeAdapter::constructAuthorizationUrl();

        expect($url)->toBeString()
            ->and($url)->toContain('/auth?')
            ->and($url)->toContain('response_type=code')
            ->and($url)->toContain('partner_id='.config('services.shopee.partner_id'))
            ->and($url)->toContain('state='.session('shopee_oauth_state'));
    });
});

describe('handleCallback', function () {
    it('exchanges a shop_id grant into a single neutral token response', function () {
        session(['shopee_oauth_state' => 'state-123']);

        MockClient::global([
            GetAccessToken::class => MockResponse::make([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expire_in' => 3600,
            ]),
        ]);

        $request = Request::create('/callback', 'GET', [
            'code' => 'auth-code',
            'shop_id' => '555',
            'state' => 'state-123',
        ]);

        $tokens = ShopeeAdapter::handleCallback(new HandleCallbackRequest($request));

        expect($tokens)->toBeInstanceOf(Collection::class)
            ->and($tokens)->toHaveCount(1)
            ->and($tokens->first())->toBeInstanceOf(GetTokenResponseData::class)
            ->and($tokens[0]->externalShopId)->toBe('555')
            ->and($tokens[0]->shopType)->toBe(ShopsEnum::SHOPEE)
            ->and($tokens[0]->isActive)->toBeTrue()
            ->and($tokens[0]->authConfiguration['auth']['access_token']['token'])->toBe('access-token')
            ->and($tokens[0]->authConfiguration['auth']['refresh_token']['token'])->toBe('refresh-token');
    });

    it('fans a main_account_id grant out into one token response per shop', function () {
        session(['shopee_oauth_state' => 'state-123']);

        MockClient::global([
            GetAccessToken::class => MockResponse::make([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expire_in' => 3600,
                'shop_id_list' => [111, 222],
            ]),
        ]);

        $request = Request::create('/callback', 'GET', [
            'code' => 'auth-code',
            'main_account_id' => '999',
            'state' => 'state-123',
        ]);

        $tokens = ShopeeAdapter::handleCallback(new HandleCallbackRequest($request));

        expect($tokens)->toHaveCount(2)
            ->and($tokens[0]->externalShopId)->toBe('111')
            ->and($tokens[1]->externalShopId)->toBe('222');
    });

    it('throws a ShopIntegrationException when the callback state does not match the session', function () {
        session(['shopee_oauth_state' => 'expected-state']);

        $request = Request::create('/callback', 'GET', [
            'code' => 'auth-code',
            'shop_id' => '555',
            'state' => 'tampered-state',
        ]);

        ShopeeAdapter::handleCallback(new HandleCallbackRequest($request));
    })->throws(ShopIntegrationException::class);
});
