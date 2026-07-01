<?php

use App\Integrations\Shopee\Data\PackageDetailData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Orders\GetPackageDetail;
use App\Integrations\Shopee\ShopeeClient;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->packageNumberList = ['1st',  '2end', '3rd'];
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->expireIn = time();
    $this->idType = 'shop_id';

    $this->request = new GetPackageDetail(
        $this->packageNumberList
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
    it('construct correct query paramter', function () {
        expect($this->request->defaultQuery())->toBe([
            'package_number_list' => implode(',', $this->packageNumberList),
        ]);
    });

    it('uses the correct endpoint for the request', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/get_package_detail');
    });
});

describe('response', function () {
    it('casts the full dto collection, including the optional fields, from the response', function () {

        $requestMock = new MockClient([
            GetPackageDetail::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'package_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'package_number' => 'PKG-1',
                            'fulfillment_status' => 'LOGISTICS_READY',
                            'logistics_channel_id' => 10001,
                            'shipping_carrier' => 'Standard Delivery',
                            'tracking_number' => 'TRK-123',
                            'is_shipment_arranged' => true,
                        ],
                        [
                            'order_sn' => '2404098R48U37H',
                            'package_number' => 'PKG-2',
                            'fulfillment_status' => 'PENDING',
                            'logistics_channel_id' => 20002,
                            'shipping_carrier' => 'Express',
                            'tracking_number' => 'TRK-456',
                            'is_shipment_arranged' => false,
                        ],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getPackageDetail($this->packageNumberList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(2)
            ->and($response->first())->toBeInstanceOf(PackageDetailData::class)
            ->and($response[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response[0]->packageNumber)->toBe('PKG-1')
            ->and($response[0]->fulfillmentStatus)->toBe('LOGISTICS_READY')
            ->and($response[0]->logisticsChannelId)->toBe(10001)
            ->and($response[0]->shippingCarrier)->toBe('Standard Delivery')
            ->and($response[0]->trackingNumber)->toBe('TRK-123')
            ->and($response[0]->isShipmentArranged)->toBeTrue()
            ->and($response[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response[1]->packageNumber)->toBe('PKG-2')
            ->and($response[1]->fulfillmentStatus)->toBe('PENDING')
            ->and($response[1]->isShipmentArranged)->toBeFalse();
    });

    it('casts the dto when only the required fields are returned', function () {

        $requestMock = new MockClient([
            GetPackageDetail::class => MockResponse::make([
                'response' => [
                    'package_list' => [
                        [
                            'order_sn' => '201218V2Y6E59M',
                            'package_number' => 'PKG-1',
                            'fulfillment_status' => 'LOGISTICS_READY',
                        ],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getPackageDetail($this->packageNumberList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(1)
            ->and($response[0])->toBeInstanceOf(PackageDetailData::class)
            ->and($response[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response[0]->packageNumber)->toBe('PKG-1')
            ->and($response[0]->fulfillmentStatus)->toBe('LOGISTICS_READY')
            ->and($response[0]->logisticsChannelId)->toBeNull()
            ->and($response[0]->shippingCarrier)->toBeNull()
            ->and($response[0]->trackingNumber)->toBeNull()
            ->and($response[0]->isShipmentArranged)->toBeNull()
            ->and($response[0]->itemList)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock = new MockClient([
            GetPackageDetail::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getPackageDetail($this->packageNumberList);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $requestMock = new MockClient([
            GetPackageDetail::class => MockResponse::make([
                'response' => [
                    'package_list' => [
                        ['order_sn' => '201218V2Y6E59M'], // missing required package_number & fulfillment_status
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getPackageDetail($this->packageNumberList);
    })->throws(ShopeeException::class);
});
