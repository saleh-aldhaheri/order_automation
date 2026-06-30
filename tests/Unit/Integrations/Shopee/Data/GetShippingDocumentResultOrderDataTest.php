<?php

use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;

it('constructs with all fields including the optional ones', function () {
    $data = new GetShippingDocumentResultOrderData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
        shippingDocumentType: ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL,
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->shippingDocumentType)->toBe(ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL);
});

it('hydrates snake_case input into camelCase properties and serializes the enum back to its value', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
        'shipping_document_type' => 'NORMAL_AIR_WAYBILL',
    ];

    $data = GetShippingDocumentResultOrderData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->shippingDocumentType)->toBe(ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL)
        ->and($data->toArray())->toEqual($input);
});
