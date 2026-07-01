<?php

use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\ShippingAddressData;
use App\Integrations\Shopee\Data\ShippingBranchData;
use App\Integrations\Shopee\Data\ShippingDropoffData;
use App\Integrations\Shopee\Data\ShippingInfoNeededData;
use App\Integrations\Shopee\Data\ShippingPickupData;
use App\Integrations\Shopee\Data\ShippingSlugData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Logistics\GetShippingParameter;
use App\Integrations\Shopee\ShopeeClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = (int) config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = bin2hex(random_bytes(16));
    $this->orderSn = bin2hex(random_bytes(16));
    $this->shopId = 10;
    $this->packageNumber = (string) random_int(1, 10);

    $this->request = new GetShippingParameter(
        $this->orderSn,
        $this->packageNumber
    );

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        accessToken: $this->accessToken,
        shopId: $this->shopId,
    );
});

describe('request', function () {
    it('builds the query parameters', function (string $orderSn, ?string $packageNumber) {
        $request = new GetShippingParameter($orderSn, $packageNumber);
        $keys = ['order_sn'];
        if ($packageNumber) {
            $keys[] = 'package_number';
        }
        expect($request->defaultQuery())->toHaveKeys($keys);
    })->with([
        ['orderSn', null],
        ['orderSn', 'packageNumber'],
    ]);

    it('uses the correct endpoint', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/get_shipping_parameter');
    });
});

describe('response', function () {
    it('casts the full dto including the optional parameters', function () {
        $mockRequest = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'info_needed' => [
                        'dropoff' => ['branch_id'],
                        'pickup' => ['address_id', 'pickup_time_id'],
                        'non_integrated' => ['tracking_number'],
                    ],
                    'dropoff' => [
                        'branch_list' => [
                            ['branch_id' => 101, 'region' => 'TW', 'city' => 'Taipei'],
                        ],
                        'slug_list' => [
                            ['slug' => 'fmt', 'slug_name' => 'FamilyMart'],
                        ],
                    ],
                    'pickup' => [
                        'address_list' => [
                            ['address_id' => 202, 'region' => 'TW', 'city' => 'Taipei', 'address' => '123 Main St'],
                        ],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getShippingParameter($this->orderSn, $this->packageNumber);

        expect($response)->toBeInstanceOf(GetShippingParameterData::class)
            ->and($response->infoNeeded)->toBeInstanceOf(ShippingInfoNeededData::class)
            ->and($response->infoNeeded->dropoff)->toBe(['branch_id'])
            ->and($response->infoNeeded->pickup)->toBe(['address_id', 'pickup_time_id'])
            ->and($response->infoNeeded->nonIntegrated)->toBe(['tracking_number'])
            ->and($response->dropoff)->toBeInstanceOf(ShippingDropoffData::class)
            ->and($response->dropoff->branchList)->toHaveCount(1)
            ->and($response->dropoff->branchList[0])->toBeInstanceOf(ShippingBranchData::class)
            ->and($response->dropoff->branchList[0]->branchId)->toBe(101)
            ->and($response->dropoff->branchList[0]->region)->toBe('TW')
            ->and($response->dropoff->branchList[0]->city)->toBe('Taipei')
            ->and($response->dropoff->slugList[0])->toBeInstanceOf(ShippingSlugData::class)
            ->and($response->dropoff->slugList[0]->slug)->toBe('fmt')
            ->and($response->dropoff->slugList[0]->slugName)->toBe('FamilyMart')
            ->and($response->pickup)->toBeInstanceOf(ShippingPickupData::class)
            ->and($response->pickup->addressList)->toHaveCount(1)
            ->and($response->pickup->addressList[0])->toBeInstanceOf(ShippingAddressData::class)
            ->and($response->pickup->addressList[0]->addressId)->toBe(202)
            ->and($response->pickup->addressList[0]->address)->toBe('123 Main St');
    });

    it('casts the dto when only the required fields are returned', function () {
        $mockRequest = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'response' => [
                    'info_needed' => [
                        'pickup' => ['address_id'],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getShippingParameter($this->orderSn, $this->packageNumber);

        expect($response)->toBeInstanceOf(GetShippingParameterData::class)
            ->and($response->infoNeeded)->toBeInstanceOf(ShippingInfoNeededData::class)
            ->and($response->infoNeeded->pickup)->toBe(['address_id'])
            ->and($response->infoNeeded->dropoff)->toBeNull()
            ->and($response->infoNeeded->nonIntegrated)->toBeNull()
            ->and($response->dropoff)->toBeNull()
            ->and($response->pickup)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $mockRequest = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getShippingParameter($this->orderSn, $this->packageNumber);

    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $mockRequest = new MockClient([
            GetShippingParameter::class => MockResponse::make([
                'response' => [
                    'fake' => 'fake', // missing the required info_needed
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getShippingParameter($this->orderSn, $this->packageNumber);
    })->throws(ShopeeException::class);
});
