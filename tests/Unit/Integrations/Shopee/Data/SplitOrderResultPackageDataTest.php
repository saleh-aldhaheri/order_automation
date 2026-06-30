<?php

use App\Integrations\Shopee\Data\SplitOrderResultPackageData;

it('constructs with all fields including the optional ones', function () {
    $data = new SplitOrderResultPackageData(
        packageNumber: 'PKG-1',
        itemList: [['item_id' => 100, 'model_id' => 0]],
    );

    expect($data->packageNumber)->toBe('PKG-1')
        ->and($data->itemList)->toBe([['item_id' => 100, 'model_id' => 0]]);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'package_number' => 'PKG-1',
        'item_list' => [['item_id' => 100, 'model_id' => 0]],
    ];

    $data = SplitOrderResultPackageData::from($input);

    expect($data->packageNumber)->toBe('PKG-1')
        ->and($data->itemList)->toBe([['item_id' => 100, 'model_id' => 0]])
        ->and($data->toArray())->toEqual($input);
});
