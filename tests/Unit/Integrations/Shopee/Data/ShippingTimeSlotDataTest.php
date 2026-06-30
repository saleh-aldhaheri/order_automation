<?php

use App\Integrations\Shopee\Data\ShippingTimeSlotData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingTimeSlotData(
        date: 1700000000,
        timeText: '09:00 - 12:00',
        pickupTimeId: 'slot-1',
        flags: ['recommended'],
    );

    expect($data->date)->toBe(1700000000)
        ->and($data->timeText)->toBe('09:00 - 12:00')
        ->and($data->pickupTimeId)->toBe('slot-1')
        ->and($data->flags)->toBe(['recommended']);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'date' => 1700000000,
        'time_text' => '09:00 - 12:00',
        'pickup_time_id' => 'slot-1',
        'flags' => ['recommended'],
    ];

    $data = ShippingTimeSlotData::from($input);

    expect($data->date)->toBe(1700000000)
        ->and($data->timeText)->toBe('09:00 - 12:00')
        ->and($data->pickupTimeId)->toBe('slot-1')
        ->and($data->flags)->toBe(['recommended'])
        ->and($data->toArray())->toEqual($input);
});
