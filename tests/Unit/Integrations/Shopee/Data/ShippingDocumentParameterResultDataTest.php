<?php

use App\Integrations\Shopee\Data\ShippingDocumentParameterResultData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingDocumentParameterResultData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
        suggestShippingDocumentType: 'NORMAL_AIR_WAYBILL',
        selectableShippingDocumentType: ['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL'],
        failError: 'logistics.error_param',
        failMessage: 'Order not found.',
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->suggestShippingDocumentType)->toBe('NORMAL_AIR_WAYBILL')
        ->and($data->selectableShippingDocumentType)->toBe(['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL'])
        ->and($data->failError)->toBe('logistics.error_param')
        ->and($data->failMessage)->toBe('Order not found.');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
        'suggest_shipping_document_type' => 'NORMAL_AIR_WAYBILL',
        'selectable_shipping_document_type' => ['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL'],
        'fail_error' => 'logistics.error_param',
        'fail_message' => 'Order not found.',
    ];

    $data = ShippingDocumentParameterResultData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->suggestShippingDocumentType)->toBe('NORMAL_AIR_WAYBILL')
        ->and($data->selectableShippingDocumentType)->toBe(['NORMAL_AIR_WAYBILL', 'THERMAL_AIR_WAYBILL'])
        ->and($data->toArray())->toEqual($input);
});
