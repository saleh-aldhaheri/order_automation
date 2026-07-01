<?php

use App\Integrations\Shopee\Enums\ShopeeDocumentStatus;

it('it has expected enums cases', function () {
    expect(count(ShopeeDocumentStatus::cases()))->toBe(3)
        ->and(ShopeeDocumentStatus::FAILED->value)->toBe('failed')
        ->and(ShopeeDocumentStatus::READY->value)->toBe('ready')
        ->and(ShopeeDocumentStatus::PROCESSING->value)->toBe('processing');

});

it('returns correct description per status', function () {
    expect(ShopeeDocumentStatus::PROCESSING->description())
        ->toContain('still being generated')
        ->and(ShopeeDocumentStatus::READY->description())
        ->toContain('can now be downloaded')
        ->and(ShopeeDocumentStatus::FAILED->description())
        ->toContain('failed');
});

it('only READY is downloadable', function () {
    expect(ShopeeDocumentStatus::READY->isDownloadable())->toBeTrue()
        ->and(ShopeeDocumentStatus::PROCESSING->isDownloadable())->toBeFalse()
        ->and(ShopeeDocumentStatus::FAILED->isDownloadable())->toBeFalse();
});

it('only PROCESSING should be polled', function () {
    expect(ShopeeDocumentStatus::PROCESSING->shouldPoll())->toBeTrue()
        ->and(ShopeeDocumentStatus::READY->shouldPoll())->toBeFalse()
        ->and(ShopeeDocumentStatus::FAILED->shouldPoll())->toBeFalse();
});

it('only FAILED returns hasFailed true', function () {
    expect(ShopeeDocumentStatus::FAILED->hasFailed())->toBeTrue()
        ->and(ShopeeDocumentStatus::PROCESSING->hasFailed())->toBeFalse()
        ->and(ShopeeDocumentStatus::READY->hasFailed())->toBeFalse();
});
