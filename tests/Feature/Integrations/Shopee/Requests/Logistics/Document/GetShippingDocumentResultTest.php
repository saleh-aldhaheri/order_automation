<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentResult;
use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Data\ShippingDocumentResultData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = (int) config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = bin2hex(random_bytes(16));
    $this->shopId = 10;
    $this->orderSn = '201218V2Y6E59M';
    $this->packageNumber = 'PKG-1';

    $this->orderList = [
        new GetShippingDocumentResultOrderData(
            orderSn: $this->orderSn,
            packageNumber: $this->packageNumber,
            shippingDocumentType: ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL,
        ),
        new GetShippingDocumentResultOrderData(orderSn: '2404098R48U37H'),
    ];

    $this->request = new GetShippingDocumentResult($this->orderList);

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        accessToken: $this->accessToken,
        shopId: $this->shopId,
    );
});

describe('request', function() {
    it('builds the body from the order list, casting the enum and dropping null fields', function() {
        expect($this->request->body()->all())->toBe([
            'order_list' => [
                [
                    'order_sn' => $this->orderSn,
                    'package_number' => $this->packageNumber,
                    'shipping_document_type' => 'NORMAL_AIR_WAYBILL',
                ],
                ['order_sn' => '2404098R48U37H'],
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/get_shipping_document_result');
    });
});


describe('response', function() {
    it('casts the full dto collection, including the optional fields, from the response', function() {
        $mockRequest = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'result_list' => [
                        [
                            'order_sn' => $this->orderSn,
                            'package_number' => $this->packageNumber,
                            'status' => 'READY',
                        ],
                        [
                            'order_sn' => '2404098R48U37H',
                            'status' => 'FAILED',
                            'fail_error' => 'logistics.error_status',
                            'fail_message' => 'Document generation failed.',
                        ],
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getShippingDocumentResult($this->orderList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(2)
            ->and($response->first())->toBeInstanceOf(ShippingDocumentResultData::class)
            ->and($response[0]->orderSn)->toBe($this->orderSn)
            ->and($response[0]->packageNumber)->toBe($this->packageNumber)
            ->and($response[0]->status)->toBe('READY')
            ->and($response[0]->failError)->toBeNull()
            ->and($response[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response[1]->status)->toBe('FAILED')
            ->and($response[1]->failError)->toBe('logistics.error_status')
            ->and($response[1]->failMessage)->toBe('Document generation failed.');
    });

    it('casts the dto when only the required fields are returned', function() {
        $mockRequest = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        ['order_sn' => $this->orderSn],
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getShippingDocumentResult($this->orderList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(1)
            ->and($response[0])->toBeInstanceOf(ShippingDocumentResultData::class)
            ->and($response[0]->orderSn)->toBe($this->orderSn)
            ->and($response[0]->packageNumber)->toBeNull()
            ->and($response[0]->status)->toBeNull()
            ->and($response[0]->failError)->toBeNull()
            ->and($response[0]->failMessage)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $mockRequest = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getShippingDocumentResult($this->orderList);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function() {
        $mockRequest = new MockClient([
            GetShippingDocumentResult::class => MockResponse::make([
                'response' => [
                    'result_list' => [
                        ['package_number' => $this->packageNumber], // missing the required order_sn
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getShippingDocumentResult($this->orderList);
    })->throws(ShopeeException::class);
});
