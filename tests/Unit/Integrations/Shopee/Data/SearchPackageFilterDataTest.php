<?php

use App\Integrations\Shopee\Data\SearchPackageFilterData;

it('constructs with all fields including the optional ones', function () {
    $data = new SearchPackageFilterData(
        packageStatus: 2,
        productLocationIds: ['IDG', 'IDH'],
        logisticsChannelIds: [10001, 20002],
        fulfillmentType: 1,
        invoicePending: true,
        sortingGroup: 3,
        orderType: 4,
        isPreOrder: 0,
        shippingPriority: 5,
    );

    expect($data->packageStatus)->toBe(2)
        ->and($data->productLocationIds)->toBe(['IDG', 'IDH'])
        ->and($data->logisticsChannelIds)->toBe([10001, 20002])
        ->and($data->fulfillmentType)->toBe(1)
        ->and($data->invoicePending)->toBeTrue()
        ->and($data->sortingGroup)->toBe(3)
        ->and($data->orderType)->toBe(4)
        ->and($data->isPreOrder)->toBe(0)
        ->and($data->shippingPriority)->toBe(5);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'package_status' => 2,
        'product_location_ids' => ['IDG', 'IDH'],
        'logistics_channel_ids' => [10001, 20002],
        'fulfillment_type' => 1,
        'invoice_pending' => true,
        'sorting_group' => 3,
        'order_type' => 4,
        'is_pre_order' => 0,
        'shipping_priority' => 5,
    ];

    $data = SearchPackageFilterData::from($input);

    expect($data->packageStatus)->toBe(2)
        ->and($data->productLocationIds)->toBe(['IDG', 'IDH'])
        ->and($data->logisticsChannelIds)->toBe([10001, 20002])
        ->and($data->invoicePending)->toBeTrue()
        ->and($data->toArray())->toEqual($input);
});
