<?php

use App\Integrations\Shopee\Data\CreateShippingDocumentOrderData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;

it('creates the data object', function () {
    $data = new CreateShippingDocumentOrderData(
        orderSn: '123456',
        packageNumber: 'PKG001',
        trackingNumber: 'TRACK123',
        shippingDocumentType: ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL,
    );

    expect($data->orderSn)->toBe('123456')
        ->and($data->packageNumber)->toBe('PKG001')
        ->and($data->trackingNumber)->toBe('TRACK123')
        ->and($data->shippingDocumentType)
        ->toBe(ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL);

});

it('hydrates from snake_case and maps correctly', function () {

    $data = CreateShippingDocumentOrderData::from([
        'order_sn' => '123456',
        'package_number' => 'PKG001',
        'tracking_number' => 'TRACK123',
        'shipping_document_type' => 'NORMAL_AIR_WAYBILL',
    ]);

    expect($data->orderSn)->toBe('123456')
        ->and($data->packageNumber)->toBe('PKG001')
        ->and($data->trackingNumber)->toBe('TRACK123')
        ->and($data->shippingDocumentType)
        ->toBe(ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL);
});
