<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A single item assigned to a package in a split_order request.
 *
 * Used as request input — `toArray()` emits snake_case keys for the body.
 * Null-valued optional fields are dropped before sending (see SplitOrder).
 */
#[MapName(SnakeCaseMapper::class)]
class SplitOrderItemData extends Data
{
    public function __construct(
        public int $itemId,
        // 0 when the item has no variation.
        public int $modelId,
        public ?int $orderItemId = null,
        // Required for add-on / bundle-deal items.
        public ?int $promotionGroupId = null,
        // Qty per package — unit-level split whitelist only.
        public ?int $modelQuantity = null,
    ) {}
}
