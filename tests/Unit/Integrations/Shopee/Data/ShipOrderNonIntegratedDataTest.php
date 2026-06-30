<?php

use App\Integrations\Shopee\Data\ShipOrderNonIntegratedData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShipOrderNonIntegratedData(
        trackingNumber: 'TRK-123',
    );

    expect($data->trackingNumber)->toBe('TRK-123');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'tracking_number' => 'TRK-123',
    ];

    $data = ShipOrderNonIntegratedData::from($input);

    expect($data->trackingNumber)->toBe('TRK-123')
        ->and($data->toArray())->toEqual($input);
});
