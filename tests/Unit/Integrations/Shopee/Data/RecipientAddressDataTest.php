<?php

use App\Integrations\Shopee\Data\RecipientAddressData;

it('constructs with all fields including the optional ones', function () {
    $data = new RecipientAddressData(
        name: 'John Doe',
        phone: '+65 1234 5678',
        town: 'Bedok',
        district: 'East',
        city: 'Singapore',
        state: 'SG',
        region: 'SG',
        zipcode: '460123',
        fullAddress: '123 Bedok North Ave',
    );

    expect($data->name)->toBe('John Doe')
        ->and($data->phone)->toBe('+65 1234 5678')
        ->and($data->town)->toBe('Bedok')
        ->and($data->district)->toBe('East')
        ->and($data->city)->toBe('Singapore')
        ->and($data->state)->toBe('SG')
        ->and($data->region)->toBe('SG')
        ->and($data->zipcode)->toBe('460123')
        ->and($data->fullAddress)->toBe('123 Bedok North Ave');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'name' => 'John Doe',
        'phone' => '+65 1234 5678',
        'town' => 'Bedok',
        'district' => 'East',
        'city' => 'Singapore',
        'state' => 'SG',
        'region' => 'SG',
        'zipcode' => '460123',
        'full_address' => '123 Bedok North Ave',
    ];

    $data = RecipientAddressData::from($input);

    expect($data->name)->toBe('John Doe')
        ->and($data->fullAddress)->toBe('123 Bedok North Ave')
        ->and($data->zipcode)->toBe('460123')
        ->and($data->toArray())->toEqual($input);
});
