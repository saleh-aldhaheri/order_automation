<?php

use App\Integrations\Shopee\Data\UpdateShippingPickupData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Logistics\UpdateShippingOrder;
use App\Integrations\Shopee\ShopeeClient;
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

    $this->pickup = new UpdateShippingPickupData(
        addressId: 202,
        pickupTimeId: 'slot-1',
    );

    $this->request = new UpdateShippingOrder(
        $this->orderSn,
        $this->pickup,
        $this->packageNumber,
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
    it('builds the body with the order, pickup and package number', function () {
        expect($this->request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
            'pickup' => [
                'address_id' => 202,
                'pickup_time_id' => 'slot-1',
            ],
            'package_number' => $this->packageNumber,
        ]);
    });

    it('omits the package number when it is not given', function () {
        $request = new UpdateShippingOrder($this->orderSn, $this->pickup);

        expect($request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
            'pickup' => [
                'address_id' => 202,
                'pickup_time_id' => 'slot-1',
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/update_shipping_order');
    });
});

describe('response', function () {
    it('returns true when Shopee reschedules the pickup', function () {
        $mockRequest = new MockClient([
            UpdateShippingOrder::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->updateShippingOrder(
            $this->orderSn,
            $this->pickup,
            $this->packageNumber,
        );

        expect($response)->toBeTrue();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $mockRequest = new MockClient([
            UpdateShippingOrder::class => MockResponse::make([
                'error' => 'logistics.error_status',
                'message' => 'Pickup can not be updated.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->updateShippingOrder(
            $this->orderSn,
            $this->pickup,
            $this->packageNumber,
        );
    })->throws(ShopeeException::class);
});
