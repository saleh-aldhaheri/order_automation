<?php

use App\Integrations\Shopee\Data\GetOrderListData;
use App\Integrations\Shopee\Data\OrderListItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new GetOrderListData(
        orderList: [new OrderListItemData(orderSn: '201218V2Y6E59M', orderStatus: 'READY_TO_SHIP')],
        more: true,
        nextCursor: '20',
    );

    expect($data->orderList)->toHaveCount(1)
        ->and($data->orderList[0])->toBeInstanceOf(OrderListItemData::class)
        ->and($data->orderList[0]->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->more)->toBeTrue()
        ->and($data->nextCursor)->toBe('20');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_list' => [
            ['order_sn' => '201218V2Y6E59M', 'order_status' => 'READY_TO_SHIP'],
        ],
        'more' => true,
        'next_cursor' => '20',
    ];

    $data = GetOrderListData::from($input);

    expect($data->orderList[0])->toBeInstanceOf(OrderListItemData::class)
        ->and($data->orderList[0]->orderStatus)->toBe('READY_TO_SHIP')
        ->and($data->more)->toBeTrue()
        ->and($data->nextCursor)->toBe('20')
        ->and($data->toArray())->toEqual($input);
});
