<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Logistics\ShipOrder;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use App\Integrations\Shopee\Data\ShipOrderDropoffData;
use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
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

    $this->pickup = new ShipOrderPickupData(
        addressId: 202,
        pickupTimeId: 'slot-1',
    );

    $this->request = new ShipOrder(
        $this->orderSn,
        $this->packageNumber,
        $this->pickup,
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
    it('builds the body with the package number and pickup method, stripping null fields', function() {
        expect($this->request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
            'package_number' => $this->packageNumber,
            'pickup' => [
                'address_id' => 202,
                'pickup_time_id' => 'slot-1',
            ],
        ]);
    });

    it('includes the chosen method as an empty object when it has no fields', function() {
        $request = new ShipOrder(
            orderSn: $this->orderSn,
            dropoff: new ShipOrderDropoffData(),
        );

        $body = $request->body()->all();

        expect($body['order_sn'])->toBe($this->orderSn)
            ->and($body['dropoff'])->toBeInstanceOf(stdClass::class)
            ->and($body)->not->toHaveKey('package_number')
            ->and($body)->not->toHaveKey('pickup');
    });

    it('builds the body for the non integrated method', function() {
        $request = new ShipOrder(
            orderSn: $this->orderSn,
            nonIntegrated: new ShipOrderNonIntegratedData(trackingNumber: 'TRK-123'),
        );

        expect($request->body()->all())->toBe([
            'order_sn' => $this->orderSn,
            'non_integrated' => [
                'tracking_number' => 'TRK-123',
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/ship_order');
    });
});


describe('response', function() {
    it('returns true when Shopee arranges the shipment', function() {
        $mockRequest = new MockClient([
            ShipOrder::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->shipOrder(
            $this->orderSn,
            $this->packageNumber,
            $this->pickup,
        );

        expect($response)->toBeTrue();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $mockRequest = new MockClient([
            ShipOrder::class => MockResponse::make([
                'error' => 'logistics.error_status',
                'message' => 'Order can not be shipped.',
                'response' => [],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->shipOrder(
            $this->orderSn,
            $this->packageNumber,
            $this->pickup,
        );
    })->throws(ShopeeException::class);
});
