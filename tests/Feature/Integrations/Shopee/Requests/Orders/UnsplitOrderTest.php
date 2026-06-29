<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Orders\UnsplitOrder;
use App\Integrations\Shopee\Exceptions\ShopeeException;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId =  config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = 'access-token';
    $this->shopId = 1;
    $this->orderSn = '201218V2Y6E59M';

    $this->request = new UnsplitOrder(
        $this->orderSn,
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
    it('builds the body with the order sn', function() {
        expect($this->request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
        ]);
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/order/unsplit_order');
    });
});


describe('response', function() {
    it('returns true when Shopee unsplits the order', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            UnsplitOrder::class =>  \Saloon\Http\Faking\MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->order()->unsplitOrder($this->orderSn);

        expect($response)->toBeTrue();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock  = new \Saloon\Http\Faking\MockClient([
            UnsplitOrder::class =>  \Saloon\Http\Faking\MockResponse::make([
                'error' => 'logistics.error_unsplit',
                'message' => 'Order can not be unsplit.',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->order()->unsplitOrder($this->orderSn);
    })->throws(ShopeeException::class);
});
