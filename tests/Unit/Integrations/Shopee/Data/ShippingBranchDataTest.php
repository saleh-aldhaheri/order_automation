<?php

use App\Integrations\Shopee\Data\ShippingBranchData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingBranchData(
        branchId: 101,
        region: 'TW',
        state: 'Taipei',
        city: 'Taipei',
        address: '123 Main St',
        zipcode: '100',
        district: 'Zhongzheng',
        town: 'Town',
    );

    expect($data->branchId)->toBe(101)
        ->and($data->region)->toBe('TW')
        ->and($data->state)->toBe('Taipei')
        ->and($data->city)->toBe('Taipei')
        ->and($data->address)->toBe('123 Main St')
        ->and($data->zipcode)->toBe('100')
        ->and($data->district)->toBe('Zhongzheng')
        ->and($data->town)->toBe('Town');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'branch_id' => 101,
        'region' => 'TW',
        'state' => 'Taipei',
        'city' => 'Taipei',
        'address' => '123 Main St',
        'zipcode' => '100',
        'district' => 'Zhongzheng',
        'town' => 'Town',
    ];

    $data = ShippingBranchData::from($input);

    expect($data->branchId)->toBe(101)
        ->and($data->district)->toBe('Zhongzheng')
        ->and($data->toArray())->toEqual($input);
});
