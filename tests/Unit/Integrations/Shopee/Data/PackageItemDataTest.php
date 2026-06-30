<?php

use App\Integrations\Shopee\Data\PackageItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new PackageItemData(
        itemId: 100,
        modelId: 200,
        itemSku: 'SKU-1',
        modelQuantity: 3,
        orderItemId: 5,
        productLocationId: 'IDG',
        promotionGroupId: 7,
    );

    expect($data->itemId)->toBe(100)
        ->and($data->modelId)->toBe(200)
        ->and($data->itemSku)->toBe('SKU-1')
        ->and($data->modelQuantity)->toBe(3)
        ->and($data->orderItemId)->toBe(5)
        ->and($data->productLocationId)->toBe('IDG')
        ->and($data->promotionGroupId)->toBe(7);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'item_id' => 100,
        'model_id' => 200,
        'item_sku' => 'SKU-1',
        'model_quantity' => 3,
        'order_item_id' => 5,
        'product_location_id' => 'IDG',
        'promotion_group_id' => 7,
    ];

    $data = PackageItemData::from($input);

    expect($data->itemId)->toBe(100)
        ->and($data->modelId)->toBe(200)
        ->and($data->itemSku)->toBe('SKU-1')
        ->and($data->modelQuantity)->toBe(3)
        ->and($data->orderItemId)->toBe(5)
        ->and($data->productLocationId)->toBe('IDG')
        ->and($data->promotionGroupId)->toBe(7)
        ->and($data->toArray())->toEqual($input);
});
