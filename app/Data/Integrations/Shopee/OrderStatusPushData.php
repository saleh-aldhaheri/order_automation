<?php

namespace App\Data\Integrations\Shopee;


use App\Enums\Integrations\ShopeeOrderStatusEnum;
use App\Enums\OrderStatusEnum;
use Spatie\LaravelData\Data;

class OrderStatusPushData extends Data
{
    public function __construct(
        public int $code,
        public int $shopId,
        public int $timestamp,
        public string $orderSn,
        public ShopeeOrderStatusEnum $status,
        public OrderStatusEnum $localStatus,
        public int $updateTime,
        public ?string $completedScenario = null,
    ) {}

    /**
     * Map a Shopee `order_status_push` webhook payload into a single flat DTO.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = $payload['data'] ?? [];

        $status = ShopeeOrderStatusEnum::from((string) ($data['status'] ?? ''));

        return new self(
            code: (int) ($payload['code'] ?? 0),
            shopId: (int) ($payload['shop_id'] ?? 0),
            timestamp: (int) ($payload['timestamp'] ?? 0),
            orderSn: (string) ($data['ordersn'] ?? ''),
            status: $status,
            localStatus: $status->toLocalStatus(),
            updateTime: (int) ($data['update_time'] ?? 0),
            completedScenario: $data['completed_scenario'] ?? null,
        );
    }
}
