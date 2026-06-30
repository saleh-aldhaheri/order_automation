<?php

use App\Integrations\Shopee\Data\ShippingAddressData;
use App\Integrations\Shopee\Data\ShippingTimeSlotData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingAddressData(
        addressId: 202,
        region: 'TW',
        state: 'Taipei',
        city: 'Taipei',
        district: 'Zhongzheng',
        town: 'Town',
        address: '123 Main St',
        zipcode: '100',
        addressFlag: ['default_address'],
        timeSlotList: [new ShippingTimeSlotData(date: 1700000000, pickupTimeId: 'slot-1')],
    );

    expect($data->addressId)->toBe(202)
        ->and($data->addressFlag)->toBe(['default_address'])
        ->and($data->timeSlotList[0])->toBeInstanceOf(ShippingTimeSlotData::class)
        ->and($data->timeSlotList[0]->pickupTimeId)->toBe('slot-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
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
    ];

    $data = ShippingAddressData::from($input);

    expect($data->addressId)->toBe(202)
        ->and($data->addressFlag)->toBe(['default_address'])
        ->and($data->timeSlotList[0])->toBeInstanceOf(ShippingTimeSlotData::class)
        ->and($data->timeSlotList[0]->pickupTimeId)->toBe('slot-1')
        ->and($data->toArray())->toEqual($input);
});
