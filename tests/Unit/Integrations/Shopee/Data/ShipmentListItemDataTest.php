<?php

use App\Integrations\Shopee\Data\ShipmentListItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShipmentListItemData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
    ];

    $data = ShipmentListItemData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->toArray())->toEqual($input);
});
