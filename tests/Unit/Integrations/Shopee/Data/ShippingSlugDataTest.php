<?php

use App\Integrations\Shopee\Data\ShippingSlugData;

it('constructs with all fields including the optional ones', function () {
    $data = new ShippingSlugData(
        slug: 'fmt',
        slugName: 'FamilyMart',
    );

    expect($data->slug)->toBe('fmt')
        ->and($data->slugName)->toBe('FamilyMart');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'slug' => 'fmt',
        'slug_name' => 'FamilyMart',
    ];

    $data = ShippingSlugData::from($input);

    expect($data->slug)->toBe('fmt')
        ->and($data->slugName)->toBe('FamilyMart')
        ->and($data->toArray())->toEqual($input);
});
