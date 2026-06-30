<?php

use App\Integrations\Shopee\Data\ShippingPickupData;
use App\Integrations\Shopee\Data\ShippingAddressData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingPickupData(
        addressList: [new ShippingAddressData(addressId: 202, city: 'Taipei')],
    );

    expect($data->addressList[0])->toBeInstanceOf(ShippingAddressData::class)
        ->and($data->addressList[0]->addressId)->toBe(202)
        ->and($data->addressList[0]->city)->toBe('Taipei');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
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
    ];

    $data = ShippingPickupData::from($input);

    expect($data->addressList[0])->toBeInstanceOf(ShippingAddressData::class)
        ->and($data->addressList[0]->addressId)->toBe(202)
        ->and($data->addressList[0]->timeSlotList[0]->pickupTimeId)->toBe('slot-1')
        ->and($data->toArray())->toEqual($input);
});
