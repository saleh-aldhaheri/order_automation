<?php

use App\Integrations\Shopee\Data\SplitOrderData;
use App\Integrations\Shopee\Data\SplitOrderResultPackageData;

it('constructs with all fields including the optional ones', function () {
    $data = new SplitOrderData(
        orderSn: '201218V2Y6E59M',
        packageList: [
            new SplitOrderResultPackageData(packageNumber: 'PKG-1', itemList: [['item_id' => 100]]),
        ],
    );

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageList)->toHaveCount(1)
        ->and($data->packageList[0])->toBeInstanceOf(SplitOrderResultPackageData::class)
        ->and($data->packageList[0]->packageNumber)->toBe('PKG-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'order_sn' => '201218V2Y6E59M',
        'package_list' => [
            ['package_number' => 'PKG-1', 'item_list' => [['item_id' => 100]]],
        ],
    ];

    $data = SplitOrderData::from($input);

    expect($data->orderSn)->toBe('201218V2Y6E59M')
        ->and($data->packageList[0])->toBeInstanceOf(SplitOrderResultPackageData::class)
        ->and($data->packageList[0]->packageNumber)->toBe('PKG-1')
        ->and($data->toArray())->toEqual($input);
});
