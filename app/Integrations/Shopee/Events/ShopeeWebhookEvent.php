<?php

namespace App\Integrations\Shopee\Events;

use App\Integrations\Shopee\Enums\ShopeeEventsEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopeeWebhookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public array $payload,
        public ShopeeEventsEnum $eventType
    ) {}
}
