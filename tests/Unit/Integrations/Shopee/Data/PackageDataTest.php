<?php

use App\Integrations\Shopee\Data\PackageData;
use App\Integrations\Shopee\Data\PackageItemData;

it('constructs with all fields including the optional ones', function () {
    $data = new PackageData(
        packageNumber: 'PKG-1',
        logisticsStatus: 'LOGISTICS_READY',
        logisticsChannelId: 10001,
        shippingCarrier: 'Standard Delivery',
        parcelChargeableWeightGram: 500,
        groupShipmentId: 'GRP-1',
        allowSelfDesignAwb: false,
        sortingGroup: 'group-a',
        itemList: [new PackageItemData(itemId: 100, modelId: 200)],
    );

    expect($data->packageNumber)->toBe('PKG-1')
        ->and($data->logisticsStatus)->toBe('LOGISTICS_READY')
        ->and($data->parcelChargeableWeightGram)->toBe(500)
        ->and($data->itemList[0])->toBeInstanceOf(PackageItemData::class)
        ->and($data->itemList[0]->itemId)->toBe(100);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'package_number' => 'PKG-1',
        'logistics_status' => 'LOGISTICS_READY',
        'logistics_channel_id' => 10001,
        'shipping_carrier' => 'Standard Delivery',
        'parcel_chargeable_weight_gram' => 500,
        'group_shipment_id' => 'GRP-1',
        'allow_self_design_awb' => false,
        'sorting_group' => 'group-a',
        'item_list' => [
            [
                'item_id' => 100,
                'model_id' => 200,
                'item_sku' => 'SKU-1',
                'model_quantity' => 3,
                'order_item_id' => 5,
                'product_location_id' => 'IDG',
                'promotion_group_id' => 7,
            ],
        ],
    ];

    $data = PackageData::from($input);

    expect($data->packageNumber)->toBe('PKG-1')
        ->and($data->logisticsChannelId)->toBe(10001)
        ->and($data->itemList[0])->toBeInstanceOf(PackageItemData::class)
        ->and($data->itemList[0]->itemSku)->toBe('SKU-1')
        ->and($data->toArray())->toEqual($input);
});
