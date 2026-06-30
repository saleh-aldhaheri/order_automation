<?php

use App\Integrations\Shopee\Data\ShippingDropoffData;
use App\Integrations\Shopee\Data\ShippingBranchData;
use App\Integrations\Shopee\Data\ShippingSlugData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingDropoffData(
        branchList: [new ShippingBranchData(branchId: 101, region: 'TW')],
        slugList: [new ShippingSlugData(slug: 'fmt', slugName: 'FamilyMart')],
    );

    expect($data->branchList[0])->toBeInstanceOf(ShippingBranchData::class)
        ->and($data->branchList[0]->branchId)->toBe(101)
        ->and($data->slugList[0])->toBeInstanceOf(ShippingSlugData::class)
        ->and($data->slugList[0]->slug)->toBe('fmt');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'branch_list' => [
            [
                'branch_id' => 101,
                'region' => 'TW',
                'state' => 'Taipei',
                'city' => 'Taipei',
                'address' => '123 Main St',
                'zipcode' => '100',
                'district' => 'Zhongzheng',
                'town' => 'Town',
            ],
        ],
        'slug_list' => [
            ['slug' => 'fmt', 'slug_name' => 'FamilyMart'],
        ],
    ];

    $data = ShippingDropoffData::from($input);

    expect($data->branchList[0])->toBeInstanceOf(ShippingBranchData::class)
        ->and($data->branchList[0]->branchId)->toBe(101)
        ->and($data->slugList[0]->slugName)->toBe('FamilyMart')
        ->and($data->toArray())->toEqual($input);
});
