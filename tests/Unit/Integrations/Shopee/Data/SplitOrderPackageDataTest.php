<?php

use App\Integrations\Shopee\Data\SplitOrderItemData;
use App\Integrations\Shopee\Data\SplitOrderPackageData;

it('constructs with all fields including the optional ones', function () {
    $data = new SplitOrderPackageData(
        itemList: [
            new SplitOrderItemData(itemId: 100, modelId: 0, orderItemId: 5, promotionGroupId: 7, modelQuantity: 2),
        ],
    );

    expect($data->itemList)->toHaveCount(1)
        ->and($data->itemList[0])->toBeInstanceOf(SplitOrderItemData::class)
        ->and($data->itemList[0]->itemId)->toBe(100);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'item_list' => [
            [
                'item_id' => 100,
                'model_id' => 0,
                'order_item_id' => 5,
                'promotion_group_id' => 7,
                'model_quantity' => 2,
            ],
        ],
    ];

    $data = SplitOrderPackageData::from($input);

    expect($data->itemList[0])->toBeInstanceOf(SplitOrderItemData::class)
        ->and($data->itemList[0]->itemId)->toBe(100)
        ->and($data->itemList[0]->modelQuantity)->toBe(2)
        ->and($data->toArray())->toEqual($input);
});
