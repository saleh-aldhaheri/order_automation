<?php

use App\Integrations\Shopee\Data\UpdateShippingPickupData;

it('constructs with all fields including the optional ones', function () {
    $data = new UpdateShippingPickupData(
        addressId: 202,
        pickupTimeId: 'slot-1',
    );

    expect($data->addressId)->toBe(202)
        ->and($data->pickupTimeId)->toBe('slot-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'address_id' => 202,
        'pickup_time_id' => 'slot-1',
    ];

    $data = UpdateShippingPickupData::from($input);

    expect($data->addressId)->toBe(202)
        ->and($data->pickupTimeId)->toBe('slot-1')
        ->and($data->toArray())->toEqual($input);
});
