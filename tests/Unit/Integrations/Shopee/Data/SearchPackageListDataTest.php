<?php

use App\Integrations\Shopee\Data\SearchPackageListData;
use App\Integrations\Shopee\Data\SearchPackageListItemData;
use App\Integrations\Shopee\Data\SearchPackagePaginationData;

it('constructs with all fields including the optional ones', function () {
    $data = new SearchPackageListData(
        packagesList: [new SearchPackageListItemData(orderSn: '201218V2Y6E59M', packageNumber: 'PKG-1')],
        pagination: new SearchPackagePaginationData(totalCount: 1, nextCursor: '20', more: true),
    );

    expect($data->packagesList)->toHaveCount(1)
        ->and($data->packagesList[0])->toBeInstanceOf(SearchPackageListItemData::class)
        ->and($data->pagination)->toBeInstanceOf(SearchPackagePaginationData::class)
        ->and($data->pagination->more)->toBeTrue();
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'packages_list' => [
            [
                'order_sn' => '201218V2Y6E59M',
                'package_number' => 'PKG-1',
                'logistics_channel_id' => 10001,
                'product_location_id' => 'IDG',
                'sorting_group' => 'group-a',
                'is_shipment_arranged' => true,
            ],
        ],
        'pagination' => [
            'total_count' => 1,
            'next_cursor' => '20',
            'more' => true,
        ],
    ];

    $data = SearchPackageListData::from($input);

    expect($data->packagesList[0])->toBeInstanceOf(SearchPackageListItemData::class)
        ->and($data->packagesList[0]->isShipmentArranged)->toBeTrue()
        ->and($data->pagination)->toBeInstanceOf(SearchPackagePaginationData::class)
        ->and($data->pagination->totalCount)->toBe(1)
        ->and($data->toArray())->toEqual($input);
});
