<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee get_tracking_number `response` object.
 *
 * CRITICAL: the tracking number comes from the 3PL, so `trackingNumber` MAY BE
 * EMPTY. Poll at 5-minute intervals until it returns a value, or listen for the
 * order_trackingno_push (code 4) webhook to avoid polling.
 */
#[MapInputName(SnakeCaseMapper::class)]
class GetTrackingNumberData extends Data
{
    public function __construct(
        public ?string $trackingNumber = null,
        // BR correios package id.
        public ?string $plpNumber = null,
        // Cross-border seller.
        public ?string $firstMileTrackingNumber = null,
        // Cross-border BR seller.
        public ?string $lastMileTrackingNumber = null,
        // Hint when some fields are unavailable (e.g. CVS closed).
        public ?string $hint = null,
        // ID local instant / sameday.
        public ?string $pickupCode = null,
    ) {}
}
