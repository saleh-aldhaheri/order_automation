<?php

use App\Integrations\Shopee\Data\SplitOrderData;
use App\Integrations\Shopee\Data\SplitOrderItemData;
use App\Integrations\Shopee\Data\SplitOrderPackageData;
use App\Integrations\Shopee\Data\SplitOrderResultPackageData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Orders\SplitOrder;
use App\Integrations\Shopee\ShopeeClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->orderSn = '201218V2Y6E59M';

    $this->packageList = [
        new SplitOrderPackageData(
            itemList: [
                new SplitOrderItemData(
                    itemId: 100,
                    modelId: 0,
                    orderItemId: 5,
                    promotionGroupId: 7,
                    modelQuantity: 2,
                ),
            ],
        ),
    ];

    $this->request = new SplitOrder(
        $this->orderSn,
        $this->packageList,
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
    it('builds the body from the order and its package list', function () {
        expect($this->request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
            'package_list' => [
                [
                    'item_list' => [
                        [
                            'item_id' => 100,
                            'model_id' => 0,
                            'order_item_id' => 5,
                            'promotion_group_id' => 7,
                            'model_quantity' => 2,
                        ],
                    ],
                ],
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/split_order');
    });
});

describe('response', function () {
    it('casts the full dto, including the optional fields, from the response', function () {

        $requestMock = new MockClient([
            SplitOrder::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'order_sn' => $this->orderSn,
                    'package_list' => [
                        [
                            'package_number' => 'PKG-1',
                            'item_list' => [
                                ['item_id' => 100, 'model_id' => 0],
                            ],
                        ],
                        [
                            'package_number' => 'PKG-2',
                            'item_list' => [
                                ['item_id' => 200, 'model_id' => 0],
                            ],
                        ],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->splitOrder($this->orderSn, $this->packageList);

        expect($response)->toBeInstanceOf(SplitOrderData::class)
            ->and($response->orderSn)->toBe($this->orderSn)
            ->and($response->packageList)->toHaveCount(2)
            ->and($response->packageList[0])->toBeInstanceOf(SplitOrderResultPackageData::class)
            ->and($response->packageList[0]->packageNumber)->toBe('PKG-1')
            ->and($response->packageList[0]->itemList)->toBe([['item_id' => 100, 'model_id' => 0]])
            ->and($response->packageList[1]->packageNumber)->toBe('PKG-2');
    });

    it('casts the dto when only the required fields are returned', function () {

        $requestMock = new MockClient([
            SplitOrder::class => MockResponse::make([
                'response' => [
                    'order_sn' => $this->orderSn,
                    'package_list' => [
                        ['package_number' => 'PKG-1'],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->splitOrder($this->orderSn, $this->packageList);

        expect($response)->toBeInstanceOf(SplitOrderData::class)
            ->and($response->orderSn)->toBe($this->orderSn)
            ->and($response->packageList)->toHaveCount(1)
            ->and($response->packageList[0])->toBeInstanceOf(SplitOrderResultPackageData::class)
            ->and($response->packageList[0]->packageNumber)->toBe('PKG-1')
            ->and($response->packageList[0]->itemList)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock = new MockClient([
            SplitOrder::class => MockResponse::make([
                'error' => 'logistics.error_split',
                'message' => 'Order can not be split.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->splitOrder($this->orderSn, $this->packageList);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $requestMock = new MockClient([
            SplitOrder::class => MockResponse::make([
                'response' => [
                    // missing the required order_sn
                    'package_list' => [
                        ['package_number' => 'PKG-1'],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->splitOrder($this->orderSn, $this->packageList);
    })->throws(ShopeeException::class);
});
