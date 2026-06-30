<?php

use App\Integrations\Shopee\Data\SearchPackagePaginationData;

it('constructs with all fields including the optional ones', function () {
    $data = new SearchPackagePaginationData(
        totalCount: 42,
        nextCursor: '20',
        more: true,
    );

    expect($data->totalCount)->toBe(42)
        ->and($data->nextCursor)->toBe('20')
        ->and($data->more)->toBeTrue();
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'total_count' => 42,
        'next_cursor' => '20',
        'more' => true,
    ];

    $data = SearchPackagePaginationData::from($input);

    expect($data->totalCount)->toBe(42)
        ->and($data->nextCursor)->toBe('20')
        ->and($data->more)->toBeTrue()
        ->and($data->toArray())->toEqual($input);
});
