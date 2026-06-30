<?php

use App\Integrations\Shopee\Data\SearchPackageSortData;

it('constructs with all fields including the optional ones', function () {
    $data = new SearchPackageSortData(
        sortType: 1,
        ascending: true,
    );

    expect($data->sortType)->toBe(1)
        ->and($data->ascending)->toBeTrue();
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'sort_type' => 1,
        'ascending' => true,
    ];

    $data = SearchPackageSortData::from($input);

    expect($data->sortType)->toBe(1)
        ->and($data->ascending)->toBeTrue()
        ->and($data->toArray())->toEqual($input);
});
