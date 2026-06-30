<?php

use App\Integrations\Shopee\Data\GetTrackingNumberData;

it('constructs with all fields including the optional ones', function () {
    $data = new GetTrackingNumberData(
        trackingNumber: 'TRK-123',
        plpNumber: 'PLP-1',
        firstMileTrackingNumber: 'FM-1',
        lastMileTrackingNumber: 'LM-1',
        hint: 'CVS closed',
        pickupCode: 'PU-1',
    );

    expect($data->trackingNumber)->toBe('TRK-123')
        ->and($data->plpNumber)->toBe('PLP-1')
        ->and($data->firstMileTrackingNumber)->toBe('FM-1')
        ->and($data->lastMileTrackingNumber)->toBe('LM-1')
        ->and($data->hint)->toBe('CVS closed')
        ->and($data->pickupCode)->toBe('PU-1');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'tracking_number' => 'TRK-123',
        'plp_number' => 'PLP-1',
        'first_mile_tracking_number' => 'FM-1',
        'last_mile_tracking_number' => 'LM-1',
        'hint' => 'CVS closed',
        'pickup_code' => 'PU-1',
    ];

    $data = GetTrackingNumberData::from($input);

    expect($data->trackingNumber)->toBe('TRK-123')
        ->and($data->firstMileTrackingNumber)->toBe('FM-1')
        ->and($data->lastMileTrackingNumber)->toBe('LM-1')
        ->and($data->pickupCode)->toBe('PU-1')
        ->and($data->toArray())->toEqual($input);
});
