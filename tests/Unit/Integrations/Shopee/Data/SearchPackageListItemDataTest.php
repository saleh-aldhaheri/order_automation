<?php

use App\Integrations\Shopee\Data\SearchPackageListItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new SearchPackageListItemData(
        orderSn: '201218V2Y6E59M',
        packageNumber: 'PKG-1',
        logisticsChannelId: 10001,
        productLocationId: 'IDG',
        sortingGroup: 'group-a',
        isShipmentArranged: true,
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageNumber)->toBe('PKG-1')
        ->and($data->logisticsChannelId)->toBe(10001)
        ->and($data->productLocationId)->toBe('IDG')
        ->and($data->sortingGroup)->toBe('group-a')
        ->and($data->isShipmentArranged)->toBeTrue();
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_number' => 'PKG-1',
        'logistics_channel_id' => 10001,
        'product_location_id' => 'IDG',
        'sorting_group' => 'group-a',
        'is_shipment_arranged' => true,
    ];

    $data = SearchPackageListItemData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->logisticsChannelId)->toBe(10001)
        ->and($data->productLocationId)->toBe('IDG')
        ->and($data->isShipmentArranged)->toBeTrue()
        ->and($data->toArray())->toEqual($input);
});
