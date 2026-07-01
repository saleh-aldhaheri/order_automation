<?php

use App\Integrations\Shopee\Data\ImageInfoData;
use App\Integrations\Shopee\Data\OrderItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new OrderItemData(
        itemId: 100,
        itemName: 'T-Shirt',
        itemSku: 'SKU-1',
        modelId: 200,
        modelName: 'Red / M',
        modelSku: 'SKU-1-RM',
        modelQuantityPurchased: 2,
        modelDiscountedPrice: 19.90,
        modelOriginalPrice: 29.90,
        wholesale: false,
        weight: 0.5,
        orderItemId: 5,
        promotionId: 11,
        promotionType: 'flash_sale',
        promotionGroupId: 7,
        addOnDeal: false,
        addOnDealId: 0,
        mainItem: true,
        isB2cOwnedItem: false,
        isPrescriptionItem: false,
        productLocationId: ['IDG'],
        imageInfo: new ImageInfoData(imageUrl: 'https://cf.shopee.sg/file/abc'),
    );

    expect($data->itemId)->toBe(100)
        ->and($data->itemName)->toBe('T-Shirt')
        ->and($data->modelDiscountedPrice)->toBe(19.90)
        ->and($data->mainItem)->toBeTrue()
        ->and($data->productLocationId)->toBe(['IDG'])
        ->and($data->imageInfo)->toBeInstanceOf(ImageInfoData::class)
        ->and($data->imageInfo->imageUrl)->toBe('https://cf.shopee.sg/file/abc');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'item_id' => 100,
        'item_name' => 'T-Shirt',
        'item_sku' => 'SKU-1',
        'model_id' => 200,
        'model_name' => 'Red / M',
        'model_sku' => 'SKU-1-RM',
        'model_quantity_purchased' => 2,
        'model_discounted_price' => 19.90,
        'model_original_price' => 29.90,
        'wholesale' => false,
        'weight' => 0.5,
        'order_item_id' => 5,
        'promotion_id' => 11,
        'promotion_type' => 'flash_sale',
        'promotion_group_id' => 7,
        'add_on_deal' => false,
        'add_on_deal_id' => 0,
        'main_item' => true,
        'is_b2c_owned_item' => false,
        'is_prescription_item' => false,
        'product_location_id' => ['IDG'],
        'image_info' => ['image_url' => 'https://cf.shopee.sg/file/abc'],
    ];

    $data = OrderItemData::from($input);

    expect($data->itemId)->toBe(100)
        ->and($data->modelQuantityPurchased)->toBe(2)
        ->and($data->isB2cOwnedItem)->toBeFalse()
        ->and($data->imageInfo)->toBeInstanceOf(ImageInfoData::class)
        ->and($data->imageInfo->imageUrl)->toBe('https://cf.shopee.sg/file/abc')
        ->and($data->toArray())->toEqual($input);
});
