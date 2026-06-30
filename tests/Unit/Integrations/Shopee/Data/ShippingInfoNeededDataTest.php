<?php

use App\Integrations\Shopee\Data\ShippingInfoNeededData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingInfoNeededData(
        dropoff: ['branch_id'],
        pickup: ['address_id', 'pickup_time_id'],
        nonIntegrated: ['tracking_number'],
    );

    expect($data->dropoff)->toBe(['branch_id'])
        ->and($data->pickup)->toBe(['address_id', 'pickup_time_id'])
        ->and($data->nonIntegrated)->toBe(['tracking_number']);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'dropoff' => ['branch_id'],
        'pickup' => ['address_id', 'pickup_time_id'],
        'non_integrated' => ['tracking_number'],
    ];

    $data = ShippingInfoNeededData::from($input);

    expect($data->dropoff)->toBe(['branch_id'])
        ->and($data->pickup)->toBe(['address_id', 'pickup_time_id'])
        ->and($data->nonIntegrated)->toBe(['tracking_number'])
        ->and($data->toArray())->toEqual($input);
});
