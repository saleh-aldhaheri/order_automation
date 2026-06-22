<?php

namespace App\Data\Integrations\Requests;

use Spatie\LaravelData\Data;

/**
 * Vendor-neutral request: which order ids to fetch.
 *
 * Translation to a specific marketplace's wire format lives in that
 * marketplace's integration layer (e.g. the Shopee GetOrderDetails request),
 * never here.
 */
class GetOrderRequestData extends Data
{
    /**
     * @param  list<string>|null  $ordersId
     */
    public function __construct(
        public ?array $ordersId,
    ) {}
}
