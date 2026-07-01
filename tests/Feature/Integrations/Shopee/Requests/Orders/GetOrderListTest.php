<?php

use App\Integrations\Shopee\Data\GetOrderListData;
use App\Integrations\Shopee\Data\OrderListItemData;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Orders\GetOrderList;
use App\Integrations\Shopee\ShopeeClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;

    $this->timeRangeField = 'create_time';
    $this->timeFrom = time() - 86400; // 24 hours ago
    $this->timeTo = time();
    $this->pageSize = 50;
    $this->cursor = bin2hex(random_bytes(16));
    $this->orderStatus = ShopeeOrderStatusEnum::READY_TO_SHIP;
    $this->responseOptionalFields = 'item_list,total_amount,buyer_user_id';
    $this->requestOrderStatusPending = true;
    $this->logisticsChannelId = 10001;

    $this->request = new GetOrderList(
        $this->timeRangeField,
        $this->timeFrom,
        $this->timeTo,
        $this->pageSize,
        $this->cursor,
        $this->orderStatus,
        $this->responseOptionalFields,
        $this->requestOrderStatusPending,
        $this->logisticsChannelId,
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

    it('builds the query parameters', function (
        $timeRangeField,
        $timeFrom,
        $timeTo,
        $pageSize,
        $cursor,
        $orderStatus,
        $responseOptionalFields,
        $requestOrderStatusPending,
        $logisticsChannelId,
    ) {
        $request = new GetOrderList(
            $timeRangeField,
            $timeFrom,
            $timeTo,
            $pageSize,
            $cursor,
            $orderStatus,
            $responseOptionalFields,
            $requestOrderStatusPending,
            $logisticsChannelId,
        );

        $query = $request->defaultQuery();

        $expected = [
            'time_range_field' => $timeRangeField,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
        ];

        if ($cursor !== null) {
            $expected['cursor'] = $cursor;
        }

        if ($orderStatus !== null) {
            $expected['order_status'] = $orderStatus->value;
        }

        if ($responseOptionalFields !== null) {
            $expected['response_optional_fields'] = $responseOptionalFields;
        }

        if ($requestOrderStatusPending !== null) {
            $expected['request_order_status_pending'] = $requestOrderStatusPending;
        }

        if ($logisticsChannelId !== null) {
            $expected['logistics_channel_id'] = $logisticsChannelId;
        }

        expect($query)
            ->toHaveKeys(array_keys($expected))
            ->toMatchArray($expected);
    })->with([
        'required only' => [
            'create_time',
            1700000000,
            1700086400,
            50,
            null,
            null,
            null,
            null,
            null,
        ],

        'with cursor' => [
            'create_time',
            1700000000,
            1700086400,
            50,
            'cursor_123',
            null,
            null,
            null,
            null,
        ],

        'with order status' => [
            'update_time',
            1700000000,
            1700086400,
            100,
            null,
            ShopeeOrderStatusEnum::READY_TO_SHIP,
            null,
            null,
            null,
        ],

        'with optional fields' => [
            'create_time',
            1700000000,
            1700086400,
            50,
            null,
            ShopeeOrderStatusEnum::COMPLETED,
            'item_list,buyer_user_id',
            true,
            10001,
        ],

        'all fields' => [
            'update_time',
            1700000000,
            1700086400,
            100,
            'cursor_xyz',
            ShopeeOrderStatusEnum::TO_RETURN,
            'item_list,total_amount',
            false,
            20002,
        ],
    ]);

    it('uses the correct endpoint', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/get_order_list');
    });
});

describe('response', function () {
    it('casts the full dto, including the optional fields, from the response', function () {

        $requestMock = new MockClient([
            GetOrderList::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'more' => true,
                    'next_cursor' => '20',
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M', 'order_status' => 'READY_TO_SHIP'],
                        ['order_sn' => '2404098R48U37H', 'order_status' => 'PROCESSED'],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getOrderList(
            $this->timeRangeField,
            $this->timeFrom,
            $this->timeTo,
        );

        expect($response)->toBeInstanceOf(GetOrderListData::class)
            ->and($response->more)->toBeTrue()
            ->and($response->nextCursor)->toBe('20')
            ->and($response->orderList)->toHaveCount(2)
            ->and($response->orderList[0])->toBeInstanceOf(OrderListItemData::class)
            ->and($response->orderList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->orderList[0]->orderStatus)->toBe('READY_TO_SHIP')
            ->and($response->orderList[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response->orderList[1]->orderStatus)->toBe('PROCESSED');
    });

    it('casts the dto when only the required fields are returned', function () {

        $requestMock = new MockClient([
            GetOrderList::class => MockResponse::make([
                'response' => [
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M'],
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getOrderList(
            $this->timeRangeField,
            $this->timeFrom,
            $this->timeTo,
        );

        expect($response)->toBeInstanceOf(GetOrderListData::class)
            ->and($response->more)->toBeFalse()
            ->and($response->nextCursor)->toBeNull()
            ->and($response->orderList)->toHaveCount(1)
            ->and($response->orderList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->orderList[0]->orderStatus)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock = new MockClient([
            GetOrderList::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getOrderList(
            $this->timeRangeField,
            $this->timeFrom,
            $this->timeTo,
        );
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $requestMock = new MockClient([
            GetOrderList::class => MockResponse::make([
                'response' => [
                    'order_list' => [
                        ['order_status' => 'READY_TO_SHIP'], // missing the required order_sn
                    ],
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getOrderList(
            $this->timeRangeField,
            $this->timeFrom,
            $this->timeTo,
        );
    })->throws(ShopeeException::class);
});
