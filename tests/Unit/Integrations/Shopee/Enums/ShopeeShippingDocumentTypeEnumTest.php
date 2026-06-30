<?php

use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;

it('has the expected enum cases', function () {
    expect(ShopeeShippingDocumentTypeEnum::cases())->toHaveCount(5)
        ->and(ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL->value)->toBe('NORMAL_AIR_WAYBILL')
        ->and(ShopeeShippingDocumentTypeEnum::THERMAL_AIR_WAYBILL->value)->toBe('THERMAL_AIR_WAYBILL')
        ->and(ShopeeShippingDocumentTypeEnum::NORMAL_JOB_AIR_WAYBILL->value)->toBe('NORMAL_JOB_AIR_WAYBILL')
        ->and(ShopeeShippingDocumentTypeEnum::THERMAL_JOB_AIR_WAYBILL->value)->toBe('THERMAL_JOB_AIR_WAYBILL')
        ->and(ShopeeShippingDocumentTypeEnum::THERMAL_UNPACKAGED_LABEL->value)->toBe('THERMAL_UNPACKAGED_LABEL');
});

it('returns the correct label for each case', function (ShopeeShippingDocumentTypeEnum $type, string $label) {
    expect($type->label())->toBe($label);
})->with([
    [ShopeeShippingDocumentTypeEnum::NORMAL_AIR_WAYBILL, 'Normal Air Waybill'],
    [ShopeeShippingDocumentTypeEnum::THERMAL_AIR_WAYBILL, 'Thermal Air Waybill'],
    [ShopeeShippingDocumentTypeEnum::NORMAL_JOB_AIR_WAYBILL, 'Normal Job Air Waybill'],
    [ShopeeShippingDocumentTypeEnum::THERMAL_JOB_AIR_WAYBILL, 'Thermal Job Air Waybill'],
    [ShopeeShippingDocumentTypeEnum::THERMAL_UNPACKAGED_LABEL, 'Thermal Unpackaged Label'],
]);
