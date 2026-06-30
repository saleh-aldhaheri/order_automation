<?php

use App\Integrations\Shopee\Data\GetShipmentListData;
use App\Integrations\Shopee\Data\ShipmentListItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new GetShipmentListData(
        orderList: [new ShipmentListItemData(orderSn: '201218V2Y6E59M', packageNumber: 'PKG-1')],
        more: true,
        nextCursor: '20',
    );

    expect($data->orderList)->toHaveCount(1)
        ->and($data->orderList[0])->toBeInstanceOf(ShipmentListItemData::class)
        ->and($data->orderList[0]->packageNumber)->toBe('PKG-1')
        ->and($data->more)->toBeTrue()
        ->and($data->nextCursor)->toBe('20');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_list' => [
            ['order_sn' => '201218V2Y6E59M', 'package_number' => 'PKG-1'],
        ],
        'more' => true,
        'next_cursor' => '20',
    ];

    $data = GetShipmentListData::from($input);

    expect($data->orderList[0])->toBeInstanceOf(ShipmentListItemData::class)
        ->and($data->orderList[0]->packageNumber)->toBe('PKG-1')
        ->and($data->more)->toBeTrue()
        ->and($data->nextCursor)->toBe('20')
        ->and($data->toArray())->toEqual($input);
});
