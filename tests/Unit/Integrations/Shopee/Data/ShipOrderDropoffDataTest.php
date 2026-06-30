<?php

use App\Integrations\Shopee\Data\ShipOrderDropoffData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShipOrderDropoffData(
        branchId: 101,
        senderRealName: 'John Doe',
        trackingNumber: 'TRK-123',
        slug: 'fmt',
    );

    expect($data->branchId)->toBe(101)
        ->and($data->senderRealName)->toBe('John Doe')
        ->and($data->trackingNumber)->toBe('TRK-123')
        ->and($data->slug)->toBe('fmt');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'branch_id' => 101,
        'sender_real_name' => 'John Doe',
        'tracking_number' => 'TRK-123',
        'slug' => 'fmt',
    ];

    $data = ShipOrderDropoffData::from($input);

    expect($data->branchId)->toBe(101)
        ->and($data->senderRealName)->toBe('John Doe')
        ->and($data->trackingNumber)->toBe('TRK-123')
        ->and($data->slug)->toBe('fmt')
        ->and($data->toArray())->toEqual($input);
});
