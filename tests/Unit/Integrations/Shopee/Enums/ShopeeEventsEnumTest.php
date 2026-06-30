<?php
use App\Integrations\Shopee\Enums\ShopeeEventsEnum;

it('it has expected enums cases',function() {
    expect(count(ShopeeEventsEnum::cases()))->toBe(7)
        ->and(ShopeeEventsEnum::SHOP_AUTH->value)->toBe(1)
        ->and(ShopeeEventsEnum::AUTH_CANCELED->value)->toBe(2)
        ->and(ShopeeEventsEnum::ORDER_STATUS->value)->toBe(3)
        ->and(ShopeeEventsEnum::ORDER_TRACKING->value)->toBe(4)
        ->and(ShopeeEventsEnum::AUTH_EXPIRY->value)->toBe(12)
        ->and(ShopeeEventsEnum::SHIPPING_DOCUMENT_STATUS->value)->toBe(15)
        ->and(ShopeeEventsEnum::PACKAGE_FULFILLMENT->value)->toBe(30);
});
