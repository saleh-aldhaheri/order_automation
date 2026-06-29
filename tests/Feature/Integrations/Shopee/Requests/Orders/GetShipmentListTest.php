<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Orders\GetShipmentList;
use App\Integrations\Shopee\Data\GetShipmentListData;
use App\Integrations\Shopee\Data\ShipmentListItemData;
use App\Integrations\Shopee\Exceptions\ShopeeException;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId =  config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->pageSize = 20;
    $this->cursor = bin2hex(random_bytes(16));

    $this->request = new GetShipmentList(
        $this->pageSize,
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
    it('builds the query with page size only', function() {
        expect($this->request->defaultQuery())->toBe([
            'page_size' => $this->pageSize,
        ]);
    });

    it('includes the cursor in the query when given', function() {
        $request = new GetShipmentList($this->pageSize, $this->cursor);

        expect($request->defaultQuery())->toBe([
            'page_size' => $this->pageSize,
            'cursor' => $this->cursor,
        ]);
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/get_shipment_list');
    });
});


describe('response', function() {
    it('casts the full dto, including the optional fields, from the response', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetShipmentList::class =>  \Saloon\Http\Faking\MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'more' => true,
                    'next_cursor' => '20',
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M', 'package_number' => 'PKG-1'],
                        ['order_sn' => '2404098R48U37H', 'package_number' => 'PKG-2'],
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getShipmentList($this->pageSize);

        expect($response)->toBeInstanceOf(GetShipmentListData::class)
            ->and($response->more)->toBeTrue()
            ->and($response->nextCursor)->toBe('20')
            ->and($response->orderList)->toHaveCount(2)
            ->and($response->orderList[0])->toBeInstanceOf(ShipmentListItemData::class)
            ->and($response->orderList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->orderList[0]->packageNumber)->toBe('PKG-1')
            ->and($response->orderList[1]->orderSn)->toBe('2404098R48U37H')
            ->and($response->orderList[1]->packageNumber)->toBe('PKG-2');
    });

    it('casts the dto when only the required fields are returned', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetShipmentList::class =>  \Saloon\Http\Faking\MockResponse::make([
                'response' => [
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M', 'package_number' => 'PKG-1'],
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->getShipmentList($this->pageSize);

        expect($response)->toBeInstanceOf(GetShipmentListData::class)
            ->and($response->more)->toBeFalse()
            ->and($response->nextCursor)->toBeNull()
            ->and($response->orderList)->toHaveCount(1)
            ->and($response->orderList[0]->orderSn)->toBe('201218V2Y6E59M')
            ->and($response->orderList[0]->packageNumber)->toBe('PKG-1');
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetShipmentList::class =>  \Saloon\Http\Faking\MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getShipmentList($this->pageSize);
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function() {
        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetShipmentList::class =>  \Saloon\Http\Faking\MockResponse::make([
                'response' => [
                    'order_list' => [
                        ['order_sn' => '201218V2Y6E59M'], // missing the required package_number
                    ],
                ],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->getShipmentList($this->pageSize);
    })->throws(ShopeeException::class);
});
