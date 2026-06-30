<?php

use App\Integrations\Shopee\Data\ShipOrderPickupData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShipOrderPickupData(
        addressId: 202,
        pickupTimeId: 'slot-1',
        trackingNumber: 'TRK-123',
    );

    expect($data->addressId)->toBe(202)
        ->and($data->pickupTimeId)->toBe('slot-1')
        ->and($data->trackingNumber)->toBe('TRK-123');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'address_id' => 202,
        'pickup_time_id' => 'slot-1',
        'tracking_number' => 'TRK-123',
    ];

    $data = ShipOrderPickupData::from($input);

    expect($data->addressId)->toBe(202)
        ->and($data->pickupTimeId)->toBe('slot-1')
        ->and($data->trackingNumber)->toBe('TRK-123')
        ->and($data->toArray())->toEqual($input);
});
