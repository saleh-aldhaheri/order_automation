<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Orders\GetOrderDetail;
use App\Integrations\Shopee\Data\GetOrderDetailsData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId =  config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->orderSnList = ['201218V2Y6E59M', '2404098R48U37H'];

    $this->request = new GetOrderDetail(
        $this->orderSnList,
        requestOrderStatusPending: true,
    );

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        accessToken: $this->accessToken,
        shopId: $this->shopId,
    );
});

describe('request', function() {
    it('builds the query parameters', function() {
        $query = $this->request->query()->all();

        expect($query['order_sn_list'])->toBe(implode(',', $this->orderSnList))
            ->and($query['request_order_status_pending'])->toBeTrue()
            ->and($query)->toHaveKey('response_optional_filed')
            ->and($query['response_optional_filed'])->toContain('item_list')
            ->and($query['response_optional_filed'])->toContain('package_list');
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/get_order_detail');
    });
});


describe('response', function() {
    it('casts the full dto collection, including the optional fields, from the response', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetOrderDetail::class =>  \Saloon\Http\Faking\MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
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
                            'buyer_user_id' => 999,
                            'buyer_username' => 'buyer_one',
                            'total_amount' => 42.50,
                            'shipping_carrier' => 'Standard Delivery',
                        ],
                        [
                            'order_sn' => '2404098R48U37H',
                            'region' => 'MY',
                            'currency' => 'MYR',
                            'cod' => true,
                            'order_status' => 'PROCESSED',
                            'create_time' => 1700100000,
                            'update_time' => 1700101000,
                            'days_to_ship' => 2,
                            'ship_by_date' => 1700400000,
                        ],
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getOrderDetail($this->orderSnList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(2)
            ->and($response->first())->toBeInstanceOf(GetOrderDetailsData::class)
            ->and($response[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response[0]->region)->toBe('SG')
            ->and($response[0]->currency)->toBe('SGD')
            ->and($response[0]->cod)->toBeFalse()
            ->and($response[0]->orderStatus)->toBe('READY_TO_SHIP')
            ->and($response[0]->createTime)->toBe(1700000000)
            ->and($response[0]->updateTime)->toBe(1700001000)
            ->and($response[0]->daysToShip)->toBe(3)
            ->and($response[0]->shipByDate)->toBe(1700300000)
            ->and($response[0]->buyerUserId)->toBe(999)
            ->and($response[0]->buyerUsername)->toBe('buyer_one')
            ->and($response[0]->totalAmount)->toBe(42.50)
            ->and($response[0]->shippingCarrier)->toBe('Standard Delivery')
            ->and($response[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response[1]->cod)->toBeTrue();
    });

    it('casts the dto when only the required fields are returned', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetOrderDetail::class =>  \Saloon\Http\Faking\MockResponse::make([
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
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getOrderDetail($this->orderSnList);

        expect($response)->toBeInstanceOf(Collection::class)
            ->and($response)->toHaveCount(1)
            ->and($response[0])->toBeInstanceOf(GetOrderDetailsData::class)
            ->and($response[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response[0]->orderStatus)->toBe('READY_TO_SHIP')
            ->and($response[0]->messageToSeller)->toBeNull()
            ->and($response[0]->buyerUserId)->toBeNull()
            ->and($response[0]->totalAmount)->toBeNull()
            ->and($response[0]->itemList)->toBeNull()
            ->and($response[0]->packageList)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetOrderDetail::class =>  \Saloon\Http\Faking\MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getOrderDetail($this->orderSnList);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function() {
        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetOrderDetail::class =>  \Saloon\Http\Faking\MockResponse::make([
                'response' => [
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M'], // missing the other required default fields
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getOrderDetail($this->orderSnList);
    })->throws(ShopeeException::class);
});
