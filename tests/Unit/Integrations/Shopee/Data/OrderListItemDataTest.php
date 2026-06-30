<?php

use App\Integrations\Shopee\Data\OrderListItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new OrderListItemData(
        orderSn: '201218V2Y6E59M',
        orderStatus: 'READY_TO_SHIP',
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->orderStatus)->toBe('READY_TO_SHIP');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'order_status' => 'READY_TO_SHIP',
    ];

    $data = OrderListItemData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->orderStatus)->toBe('READY_TO_SHIP')
        ->and($data->toArray())->toEqual($input);
});
