<?php

use App\Integrations\Shopee\Data\CreateShippingDocumentOrderData;
use App\Integrations\Shopee\Data\CreateShippingDocumentResultData;
use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\GetTrackingNumberData;
use App\Integrations\Shopee\Data\ShipOrderPickupData;
use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Data\ShippingDocumentParameterResultData;
use App\Integrations\Shopee\Data\ShippingDocumentResultData;
use App\Integrations\Shopee\Data\UpdateShippingPickupData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Logistics\Document\CreateShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\DownloadShippingDocument;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentParameter;
use App\Integrations\Shopee\Requests\Logistics\Document\GetShippingDocumentResult;
use App\Integrations\Shopee\Requests\Logistics\GetShippingParameter;
use App\Integrations\Shopee\Requests\Logistics\GetTrackingNumber;
use App\Integrations\Shopee\Requests\Logistics\ShipOrder;
use App\Integrations\Shopee\Requests\Logistics\UpdateShippingOrder;
use App\Integrations\Shopee\Resource;
use App\Integrations\Shopee\Resources\Logistics as LogisticsResource;
use App\Integrations\Shopee\ShopeeClient;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->shopeeAuthClient = new ShopeeClient(
        config('services.shopee.partner_id'),
        config('services.shopee.partner_key'),
        config('services.shopee.base_url'),
        accessToken: 'fake',
        shopId: 'fake',
        refreshToken: 'fake',
    );

    // Mock a single Shopee request class with the given body/status, then return
    // the Logistics resource ready to call.
    $this->logistics = function (string $requestClass, mixed $body, int $status = 200): LogisticsResource {
        $this->shopeeAuthClient->withMockClient(new MockClient([
            $requestClass => MockResponse::make($body, $status),
        ]));

        return $this->shopeeAuthClient->logistic();
    };
});

it('resolves the Logistics resource bound to the connector', function () {
    expect($this->shopeeAuthClient->logistic())
        ->toBeInstanceOf(Resource::class)
        ->toHaveProperty('connector');
});

describe('getShippingParameter', function () {
    it('returns GetShippingParameterData on a valid response', function () {
        $logistics = ($this->logistics)(GetShippingParameter::class, [
            'response' => [
                'info_needed' => ['pickup' => ['address_id', 'pickup_time_id']],
                'pickup' => ['address_list' => [['address_id' => 234]]],
            ],
        ]);

        $result = $logistics->getShippingParameter('201ABC');

        expect($result)->toBeInstanceOf(GetShippingParameterData::class)
            ->and($result->infoNeeded->pickup)->toBe(['address_id', 'pickup_time_id'])
            ->and($result->pickup->addressList[0]->addressId)->toBe(234);
    });

    it('throws ShopeeException when info_needed is missing (required)', function () {
        $logistics = ($this->logistics)(GetShippingParameter::class, ['foo' => 'bar']);

        expect(fn () => $logistics->getShippingParameter('201ABC'))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $logistics = ($this->logistics)(GetShippingParameter::class, ['error' => 'logistics.error', 'message' => 'not ready']);

        expect(fn () => $logistics->getShippingParameter('201ABC'))->toThrow(ShopeeException::class);
    });
});

describe('shipOrder', function () {
    it('returns true on a successful response', function () {
        $logistics = ($this->logistics)(ShipOrder::class, ['response' => []]);

        $shipped = $logistics->shipOrder('201ABC', pickup: new ShipOrderPickupData(addressId: 234));

        expect($shipped)->toBeTrue();
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $logistics = ($this->logistics)(ShipOrder::class, ['error' => 'logistics.error', 'message' => 'cannot ship']);

        expect(fn () => $logistics->shipOrder('201ABC', pickup: new ShipOrderPickupData(addressId: 234)))
            ->toThrow(ShopeeException::class);
    });
});

describe('updateShippingOrder', function () {
    it('returns true on a successful response', function () {
        $logistics = ($this->logistics)(UpdateShippingOrder::class, ['response' => []]);

        $updated = $logistics->updateShippingOrder('201ABC', new UpdateShippingPickupData(addressId: 234, pickupTimeId: 'slot-1'));

        expect($updated)->toBeTrue();
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $logistics = ($this->logistics)(UpdateShippingOrder::class, ['error' => 'logistics.error', 'message' => 'cannot reschedule']);

        expect(fn () => $logistics->updateShippingOrder('201ABC', new UpdateShippingPickupData(addressId: 234, pickupTimeId: 'slot-1')))
            ->toThrow(ShopeeException::class);
    });
});

describe('getTrackingNumber', function () {
    it('returns GetTrackingNumberData on a valid response', function () {
        $logistics = ($this->logistics)(GetTrackingNumber::class, [
            'response' => ['tracking_number' => 'TRACK123'],
        ]);

        $result = $logistics->getTrackingNumber('201ABC');

        expect($result)->toBeInstanceOf(GetTrackingNumberData::class)
            ->and($result->trackingNumber)->toBe('TRACK123');
    });

    it('returns an empty DTO when the tracking number has not been assigned yet', function () {
        $logistics = ($this->logistics)(GetTrackingNumber::class, ['foo' => 'bar']);

        $result = $logistics->getTrackingNumber('201ABC');

        expect($result)->toBeInstanceOf(GetTrackingNumberData::class)
            ->and($result->trackingNumber)->toBeNull();
    });

    it('throws ShopeeException when Shopee returns an error', function () {
        $logistics = ($this->logistics)(GetTrackingNumber::class, ['error' => 'logistics.error', 'message' => 'order not found']);

        expect(fn () => $logistics->getTrackingNumber('201ABC'))->toThrow(ShopeeException::class);
    });
});

describe('getShippingDocumentParameter', function () {
    $orderList = fn () => [new ShippingDocumentOrderData('201ABC', 'OFG1')];

    it('returns a collection of ShippingDocumentParameterResultData on a valid response', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentParameter::class, [
            'response' => ['result_list' => [[
                'order_sn' => '201ABC',
                'package_number' => 'OFG1',
                'suggest_shipping_document_type' => 'NORMAL_AIR_WAYBILL',
                'selectable_shipping_document_type' => ['NORMAL_AIR_WAYBILL'],
            ]]],
        ]);

        $result = $logistics->getShippingDocumentParameter($orderList());

        expect($result)->toBeInstanceOf(Collection::class)
            ->toHaveCount(1)
            ->and($result->first())->toBeInstanceOf(ShippingDocumentParameterResultData::class)
            ->and($result->first()->orderSn)->toBe('201ABC')
            ->and($result->first()->suggestShippingDocumentType)->toBe('NORMAL_AIR_WAYBILL');
    });

    it('returns an empty collection when there is no result_list', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentParameter::class, ['foo' => 'bar']);

        expect($logistics->getShippingDocumentParameter($orderList()))->toBeInstanceOf(Collection::class)->toBeEmpty();
    });

    it('throws ShopeeException when a result is missing the required order_sn', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentParameter::class, [
            'response' => ['result_list' => [['package_number' => 'OFG1']]],
        ]);

        expect(fn () => $logistics->getShippingDocumentParameter($orderList()))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentParameter::class, ['error' => 'logistics.error', 'message' => 'bad order']);

        expect(fn () => $logistics->getShippingDocumentParameter($orderList()))->toThrow(ShopeeException::class);
    });
});

describe('createShippingDocument', function () {
    $orderList = fn () => [new CreateShippingDocumentOrderData('201ABC', 'OFG1', 'TRACK123', ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL)];

    it('returns a collection of CreateShippingDocumentResultData on a valid response', function () use ($orderList) {
        $logistics = ($this->logistics)(CreateShippingDocument::class, [
            'response' => ['result_list' => [['order_sn' => '201ABC', 'package_number' => 'OFG1']]],
        ]);

        $result = $logistics->createShippingDocument($orderList());

        expect($result)->toBeInstanceOf(Collection::class)
            ->toHaveCount(1)
            ->and($result->first())->toBeInstanceOf(CreateShippingDocumentResultData::class)
            ->and($result->first()->orderSn)->toBe('201ABC');
    });

    it('returns an empty collection when there is no result_list', function () use ($orderList) {
        $logistics = ($this->logistics)(CreateShippingDocument::class, ['foo' => 'bar']);

        expect($logistics->createShippingDocument($orderList()))->toBeInstanceOf(Collection::class)->toBeEmpty();
    });

    it('throws ShopeeException when a result is missing the required order_sn', function () use ($orderList) {
        $logistics = ($this->logistics)(CreateShippingDocument::class, [
            'response' => ['result_list' => [['package_number' => 'OFG1']]],
        ]);

        expect(fn () => $logistics->createShippingDocument($orderList()))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () use ($orderList) {
        $logistics = ($this->logistics)(CreateShippingDocument::class, ['error' => 'logistics.error', 'message' => 'no tracking yet']);

        expect(fn () => $logistics->createShippingDocument($orderList()))->toThrow(ShopeeException::class);
    });
});

describe('getShippingDocumentResult', function () {
    $orderList = fn () => [new GetShippingDocumentResultOrderData('201ABC', 'OFG1', ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL)];

    it('returns a collection of ShippingDocumentResultData on a valid response', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentResult::class, [
            'response' => ['result_list' => [['order_sn' => '201ABC', 'package_number' => 'OFG1', 'status' => 'READY']]],
        ]);

        $result = $logistics->getShippingDocumentResult($orderList());

        expect($result)->toBeInstanceOf(Collection::class)
            ->toHaveCount(1)
            ->and($result->first())->toBeInstanceOf(ShippingDocumentResultData::class)
            ->and($result->first()->orderSn)->toBe('201ABC')
            ->and($result->first()->status)->toBe('READY');
    });

    it('returns an empty collection when there is no result_list', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentResult::class, ['foo' => 'bar']);

        expect($logistics->getShippingDocumentResult($orderList()))->toBeInstanceOf(Collection::class)->toBeEmpty();
    });

    it('throws ShopeeException when a result is missing the required order_sn', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentResult::class, [
            'response' => ['result_list' => [['status' => 'READY']]],
        ]);

        expect(fn () => $logistics->getShippingDocumentResult($orderList()))->toThrow(ShopeeException::class);
    });

    it('throws ShopeeException when Shopee returns an error', function () use ($orderList) {
        $logistics = ($this->logistics)(GetShippingDocumentResult::class, ['error' => 'logistics.error', 'message' => 'bad order']);

        expect(fn () => $logistics->getShippingDocumentResult($orderList()))->toThrow(ShopeeException::class);
    });
});

describe('downloadShippingDocument', function () {
    $orderList = fn () => [new ShippingDocumentOrderData('201ABC', 'OFG1')];

    it('returns the raw file bytes on a successful response', function () use ($orderList) {
        $logistics = ($this->logistics)(DownloadShippingDocument::class, '%PDF-1.4 raw-waybill-bytes');

        $result = $logistics->downloadShippingDocument($orderList(), ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL);

        expect($result)->toBe('%PDF-1.4 raw-waybill-bytes');
    });

    it('throws ShopeeException when the body is a Shopee error payload', function () use ($orderList) {
        $logistics = ($this->logistics)(DownloadShippingDocument::class, [
            'error' => 'logistics.error',
            'message' => 'document not ready',
        ]);

        expect(fn () => $logistics->downloadShippingDocument($orderList(), ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL))
            ->toThrow(ShopeeException::class);
    });
});
