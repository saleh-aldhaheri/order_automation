<?php

use App\Integrations\Shopee\Data\SplitOrderItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new SplitOrderItemData(
        itemId: 100,
        modelId: 0,
        orderItemId: 5,
        promotionGroupId: 7,
        modelQuantity: 2,
    );

    expect($data->itemId)->toBe(100)
        ->and($data->modelId)->toBe(0)
        ->and($data->orderItemId)->toBe(5)
        ->and($data->promotionGroupId)->toBe(7)
        ->and($data->modelQuantity)->toBe(2);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'item_id' => 100,
        'model_id' => 0,
        'order_item_id' => 5,
        'promotion_group_id' => 7,
        'model_quantity' => 2,
    ];

    $data = SplitOrderItemData::from($input);

    expect($data->itemId)->toBe(100)
        ->and($data->modelId)->toBe(0)
        ->and($data->orderItemId)->toBe(5)
        ->and($data->promotionGroupId)->toBe(7)
        ->and($data->modelQuantity)->toBe(2)
        ->and($data->toArray())->toEqual($input);
});
