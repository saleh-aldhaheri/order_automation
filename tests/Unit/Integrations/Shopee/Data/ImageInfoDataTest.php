<?php

use App\Integrations\Shopee\Data\ImageInfoData;

it('constructs with all fields including the optional ones', function () {
    $data = new ImageInfoData(
        imageUrl: 'https://cf.shopee.sg/file/abc',
    );

    expect($data->imageUrl)->toBe('https://cf.shopee.sg/file/abc');
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'image_url' => 'https://cf.shopee.sg/file/abc',
    ];

    $data = ImageInfoData::from($input);

    expect($data->imageUrl)->toBe('https://cf.shopee.sg/file/abc')
        ->and($data->toArray())->toEqual($input);
});
