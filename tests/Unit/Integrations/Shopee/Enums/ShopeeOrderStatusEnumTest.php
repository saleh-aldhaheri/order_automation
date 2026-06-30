<?php

use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;

it('has the expected enum cases', function () {
    expect(ShopeeOrderStatusEnum::cases())->toHaveCount(10)
        ->and(ShopeeOrderStatusEnum::UNPAID->value)->toBe('UNPAID')
        ->and(ShopeeOrderStatusEnum::READY_TO_SHIP->value)->toBe('READY_TO_SHIP')
        ->and(ShopeeOrderStatusEnum::COMPLETED->value)->toBe('COMPLETED')
        ->and(ShopeeOrderStatusEnum::TO_RETURN->value)->toBe('TO_RETURN');
});

it('returns the correct label and description for each status', function (
    ShopeeOrderStatusEnum $status,
    string $label,
    string $description,
) {
    expect($status->label())->toBe($label)
        ->and($status->description())->toBe($description);
})->with([
    [ShopeeOrderStatusEnum::UNPAID, 'Unpaid', 'Order is created, buyer has not paid yet.'],
    [ShopeeOrderStatusEnum::READY_TO_SHIP, 'Ready to Ship', 'Seller can arrange shipment.'],
    [ShopeeOrderStatusEnum::PROCESSED, 'Processed', 'Seller has arranged shipment online and got tracking number from 3PL.'],
    [ShopeeOrderStatusEnum::SHIPPED, 'Shipped', 'The parcel has been dropped to 3PL or picked up by 3PL.'],
    [ShopeeOrderStatusEnum::TO_CONFIRM_RECEIVE, 'To Confirm Receive', 'The order has been received by buyer.'],
    [ShopeeOrderStatusEnum::COMPLETED, 'Completed', 'The order has been completed.'],
    [ShopeeOrderStatusEnum::RETRY_SHIP, 'Retry Ship', '3PL pickup parcel failed. Need to re-arrange shipment.'],
    [ShopeeOrderStatusEnum::IN_CANCEL, 'In Cancel', "The order's cancellation is under processing."],
    [ShopeeOrderStatusEnum::CANCELLED, 'Cancelled', 'The order has been cancelled.'],
    [ShopeeOrderStatusEnum::TO_RETURN, 'To Return', 'The buyer requested to return the order and the return is processing.'],
]);
