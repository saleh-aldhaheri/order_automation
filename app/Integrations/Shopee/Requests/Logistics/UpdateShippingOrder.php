<?php

namespace App\Integrations\Shopee\Requests\Logistics;

use App\Integrations\Shopee\Data\UpdateShippingPickupData;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Update pickup address/time — pickup only.
 *
 * Conditions: fulfillment status LOGISTICS_PICKUP_RETRY, or
 * LOGISTICS_REQUEST_CREATED + Instant Order Reschedule. Use when pickup
 * was wrong or failed.
 */
class UpdateShippingOrder extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $orderSn,
        public readonly UpdateShippingPickupData $pickup,
        public readonly ?string $packageNumber = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/update_shipping_order';
    }

    protected function defaultBody(): array
    {
        $body = [
            'order_sn' => $this->orderSn,
            'pickup' => $this->pickup->toArray(),
        ];

        if ($this->packageNumber) {
            $body['package_number'] = $this->packageNumber;
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): bool
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        return true;
    }
}
