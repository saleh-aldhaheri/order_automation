<?php

use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Logistics\Document\DownloadShippingDocument;
use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
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
    $this->documentType = ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL;

    $this->orderList = [
        new ShippingDocumentOrderData(orderSn: $this->orderSn, packageNumber: $this->packageNumber),
        new ShippingDocumentOrderData(orderSn: '2404098R48U37H'),
    ];

    $this->request = new DownloadShippingDocument($this->orderList, $this->documentType);

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        accessToken: $this->accessToken,
        shopId: $this->shopId,
    );
});

describe('request', function() {
    it('builds the body with the order list and the top-level document type', function() {
        expect($this->request->body()->all())->toBe([
            'order_list' => [
                ['order_sn' => $this->orderSn, 'package_number' => $this->packageNumber],
                ['order_sn' => '2404098R48U37H'],
            ],
            'shipping_document_type' => 'NORMAL_AIR_WAYBILL',
        ]);
    });

    it('omits the document type when it is not given', function() {
        $request = new DownloadShippingDocument($this->orderList);

        expect($request->body()->all())->toBe([
            'order_list' => [
                ['order_sn' => $this->orderSn, 'package_number' => $this->packageNumber],
                ['order_sn' => '2404098R48U37H'],
            ],
        ]);
    });

    it('uses the correct endpoint for the request', function() {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/logistics/download_shipping_document');
    });
});


describe('response', function() {
    it('returns the raw document file on success', function() {
        $file = '%PDF-1.4 fake waybill bytes';

        $mockRequest = new MockClient([
            DownloadShippingDocument::class => MockResponse::make($file, 200),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $response = $this->shopeeClient->logistic()->downloadShippingDocument($this->orderList, $this->documentType);

        expect($response)->toBe($file);
    });

    it('throws a ShopeeException when the body is a JSON error envelope', function () {
        $mockRequest = new MockClient([
            DownloadShippingDocument::class => MockResponse::make([
                'error' => 'logistics.error_status',
                'message' => 'Document is not ready to download.',
                'request_id' => 'request-id',
            ], 200),
        ], 200);

        $this->shopeeClient->withMockClient($mockRequest);
        $this->shopeeClient->logistic()->downloadShippingDocument($this->orderList, $this->documentType);
    })->throws(ShopeeException::class, 'Document is not ready to download.');
});
