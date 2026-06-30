<?php

use App\Integrations\Shopee\Data\StatusInfoTagData;

it('constructs with all fields including the optional ones', function () {
    $data = new StatusInfoTagData(
        tagId: 1,
        timestamp: 1700000000,
    );

    expect($data->tagId)->toBe(1)
        ->and($data->timestamp)->toBe(1700000000);
});

it('hydrates snake_case input into camelCase properties and serializes back', function () {
    $input = [
        'tag_id' => 1,
        'timestamp' => 1700000000,
    ];

    $data = StatusInfoTagData::from($input);

    expect($data->tagId)->toBe(1)
        ->and($data->timestamp)->toBe(1700000000)
        ->and($data->toArray())->toEqual($input);
});
