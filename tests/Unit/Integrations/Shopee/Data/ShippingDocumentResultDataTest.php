<?php

use App\Integrations\Shopee\Data\ShippingDocumentResultData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingDocumentResultData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
        status: 'READY',
        failError: 'logistics.error_status',
        failMessage: 'Document generation failed.',
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->status)->toBe('READY')
        ->and($data->failError)->toBe('logistics.error_status')
        ->and($data->failMessage)->toBe('Document generation failed.');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
        'status' => 'READY',
        'fail_error' => 'logistics.error_status',
        'fail_message' => 'Document generation failed.',
    ];

    $data = ShippingDocumentResultData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->status)->toBe('READY')
        ->and($data->failError)->toBe('logistics.error_status')
        ->and($data->failMessage)->toBe('Document generation failed.')
        ->and($data->toArray())->toEqual($input);
});
