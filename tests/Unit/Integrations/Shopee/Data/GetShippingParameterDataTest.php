<?php

use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Data\ShippingInfoNeededData;
use App\Integrations\Shopee\Data\ShippingDropoffData;
use App\Integrations\Shopee\Data\ShippingPickupData;

it('constructs with all fields including the optional ones', function () {
    $data = new GetShippingParameterData(
        infoNeeded: new ShippingInfoNeededData(pickup: ['address_id']),
        dropoff: new ShippingDropoffData(),
        pickup: new ShippingPickupData(),
    );

    expect($data->infoNeeded)->toBeInstanceOf(ShippingInfoNeededData::class)
        ->and($data->infoNeeded->pickup)->toBe(['address_id'])
        ->and($data->dropoff)->toBeInstanceOf(ShippingDropoffData::class)
        ->and($data->pickup)->toBeInstanceOf(ShippingPickupData::class);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'info_needed' => [
            'dropoff' => ['branch_id'],
            'pickup' => ['address_id', 'pickup_time_id'],
            'non_integrated' => ['tracking_number'],
        ],
        'dropoff' => [
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
        ],
        'pickup' => [
            'address_list' => [
                [
                    'address_id' => 202,
                    'region' => 'TW',
                    'state' => 'Taipei',
                    'city' => 'Taipei',
                    'district' => 'Zhongzheng',
                    'town' => 'Town',
                    'address' => '123 Main St',
                    'zipcode' => '100',
                    'address_flag' => ['default_address'],
                    'time_slot_list' => [
                        [
                            'date' => 1700000000,
                            'time_text' => '09:00 - 12:00',
                            'pickup_time_id' => 'slot-1',
                            'flags' => ['recommended'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $data = GetShippingParameterData::from($input);

    expect($data->infoNeeded)->toBeInstanceOf(ShippingInfoNeededData::class)
        ->and($data->infoNeeded->dropoff)->toBe(['branch_id'])
        ->and($data->dropoff->branchList[0]->branchId)->toBe(101)
        ->and($data->pickup->addressList[0]->addressId)->toBe(202)
        ->and($data->toArray())->toEqual($input);
});
