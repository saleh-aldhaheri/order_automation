<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeeShippingDocumentTypeEnum: string
{
    case NORMAL_AIR_WAYBILL       = 'NORMAL_AIR_WAYBILL';
    case THERMAL_AIR_WAYBILL      = 'THERMAL_AIR_WAYBILL';
    case NORMAL_JOB_AIR_WAYBILL   = 'NORMAL_JOB_AIR_WAYBILL';
    case THERMAL_JOB_AIR_WAYBILL  = 'THERMAL_JOB_AIR_WAYBILL';
    case THERMAL_UNPACKAGED_LABEL = 'THERMAL_UNPACKAGED_LABEL';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL_AIR_WAYBILL       => 'Normal Air Waybill',
            self::THERMAL_AIR_WAYBILL      => 'Thermal Air Waybill',
            self::NORMAL_JOB_AIR_WAYBILL   => 'Normal Job Air Waybill',
            self::THERMAL_JOB_AIR_WAYBILL  => 'Thermal Job Air Waybill',
            self::THERMAL_UNPACKAGED_LABEL => 'Thermal Unpackaged Label',
        };
    }
}
