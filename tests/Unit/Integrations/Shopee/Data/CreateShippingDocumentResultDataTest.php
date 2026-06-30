<?php

use App\Integrations\Shopee\Data\CreateShippingDocumentResultData;

it('creates the data object', function () {
    $data = new CreateShippingDocumentResultData(
        orderSn: '123456',
        packageNumber: 'PKG001',
        failError: 'ERR_01',
        failMessage: 'Invalid order',
    );

    expect($data->orderSn)->toBe('123456')
        ->and($data->packageNumber)->toBe('PKG001')
        ->and($data->failError)->toBe('ERR_01')
        ->and($data->failMessage)->toBe('Invalid order');
});

it('hydrates from snake_case and maps correctly', function () {

    $data = CreateShippingDocumentResultData::from([
        'order_sn' => '123456',
        'package_number' => 'PKG001',
        'fail_error' => 'ERR_01',
        'fail_message' => 'Invalid order',
    ]);

    expect($data->orderSn)->toBe('123456')
        ->and($data->packageNumber)->toBe('PKG001')
        ->and($data->failError)->toBe('ERR_01')
        ->and($data->failMessage)->toBe('Invalid order');
});
