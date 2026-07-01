<?php

use App\Integrations\Shopee\Data\GetTrackingNumberData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Logistics\GetTrackingNumber;
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
    $this->optionalFields = 'first_mile_tracking_number,last_mile_tracking_number';

    $this->request = new GetTrackingNumber(
        $this->orderSn,
        $this->packageNumber,
        $this->optionalFields
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
    it('builds the query parameters', function (string $orderSn, ?string $packageNumber, ?string $optionalFields) {
        $request = new GetTrackingNumber($orderSn, $packageNumber, $optionalFields);

        $keys = ['order_sn'];

        if ($packageNumber) {
            $keys[] = 'package_number';
        }

        if ($optionalFields) {
            $keys[] = 'response_optional_fields';
        }

        expect($request->defaultQuery())->toHaveKeys($keys);

    })->with([
        ['orderSn', null,  'optional'],
        ['orderSn', 'packageNumber', null],
        ['orderSn', 'packageNumber', 'optional'],
        ['orderSn', null, null],
    ]);

    it('uses the correct endpoint', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/get_tracking_number');
    });
});

describe('response', function () {
    it('casts the full dto including the optional parameters', function () {
        $mockRequest = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'response' => [
                    'tracking_number' => 'TRK-123',
                    'plp_number' => 'PLP-1',
                    'first_mile_tracking_number' => 'FM-1',
                    'last_mile_tracking_number' => 'LM-1',
                    'hint' => 'CVS closed',
                    'pickup_code' => 'PU-1',
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getTrackingNumber(
            $this->orderSn,
            $this->packageNumber,
            $this->optionalFields,
        );

        expect($response)->toBeInstanceOf(GetTrackingNumberData::class)
            ->and($response->trackingNumber)->toBe('TRK-123')
            ->and($response->plpNumber)->toBe('PLP-1')
            ->and($response->firstMileTrackingNumber)->toBe('FM-1')
            ->and($response->lastMileTrackingNumber)->toBe('LM-1')
            ->and($response->hint)->toBe('CVS closed')
            ->and($response->pickupCode)->toBe('PU-1');
    });

    it('casts an empty dto when the tracking number is not ready yet', function () {
        $mockRequest = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->getTrackingNumber(
            $this->orderSn,
            $this->packageNumber,
            $this->optionalFields,
        );

        expect($response)->toBeInstanceOf(GetTrackingNumberData::class)
            ->and($response->trackingNumber)->toBeNull()
            ->and($response->plpNumber)->toBeNull()
            ->and($response->firstMileTrackingNumber)->toBeNull()
            ->and($response->lastMileTrackingNumber)->toBeNull()
            ->and($response->hint)->toBeNull()
            ->and($response->pickupCode)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $mockRequest = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'error' => 'common.error_auth',
                'message' => 'Invalid access_token.',
                'response' => [],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getTrackingNumber(
            $this->orderSn,
            $this->packageNumber,
            $this->optionalFields,
        );

    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $mockRequest = new MockClient([
            GetTrackingNumber::class => MockResponse::make([
                'response' => [
                    'tracking_number' => ['not', 'a', 'string'], // wrong type for a string field
                ],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->getTrackingNumber(
            $this->orderSn,
            $this->packageNumber,
            $this->optionalFields,
        );
    })->throws(ShopeeException::class);
});
