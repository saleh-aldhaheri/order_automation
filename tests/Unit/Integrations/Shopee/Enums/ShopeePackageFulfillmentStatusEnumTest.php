<?php

use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;

it('exposes the fulfillment status string constants', function () {
    expect(ShopeePackageFulfillmentStatusEnum::LOGISTICS_NOT_START)->toBe('LOGISTICS_NOT_START')
        ->and(ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY)->toBe('LOGISTICS_READY')
        ->and(ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_DONE)->toBe('LOGISTICS_PICKUP_DONE')
        ->and(ShopeePackageFulfillmentStatusEnum::LOGISTICS_LOST)->toBe('LOGISTICS_LOST');
});

it('returns the correct label and description for each status', function (string $status, string $label, string $description) {
    expect(ShopeePackageFulfillmentStatusEnum::label($status))->toBe($label)
        ->and(ShopeePackageFulfillmentStatusEnum::description($status))->toBe($description);
})->with([
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_NOT_START, 'Not Started', 'Initial status, package not ready for fulfillment.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY, 'Ready', 'Package ready for fulfillment from payment perspective (non-COD: paid; COD: passed screening). Call get_shipping_parameter here.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CREATED, 'Request Created', 'Package arranged shipment.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_DONE, 'Pickup Done', 'Package handed over to 3PL. Cannot create the waybill after this.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE, 'Delivery Done', 'Package successfully delivered.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_RETRY, 'Pickup Retry', 'Package pending 3PL retry pickup. Use update_shipping_order here.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_INVALID, 'Invalid', 'Order cancelled when package at LOGISTICS_READY.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CANCELED, 'Request Canceled', 'Order cancelled when package at LOGISTICS_REQUEST_CREATED.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_FAILED, 'Pickup Failed', 'Order cancelled by 3PL due to failed pickup, or picked up but unable to proceed with delivery.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_FAILED, 'Delivery Failed', 'Order cancelled due to 3PL delivery failure.'],
    [ShopeePackageFulfillmentStatusEnum::LOGISTICS_LOST, 'Lost', 'Order cancelled due to 3PL losing the package.'],
]);

it('falls back to the raw status for an unknown value', function () {
    expect(ShopeePackageFulfillmentStatusEnum::label('SOMETHING_ELSE'))->toBe('SOMETHING_ELSE')
        ->and(ShopeePackageFulfillmentStatusEnum::description('SOMETHING_ELSE'))->toBe('');
});
