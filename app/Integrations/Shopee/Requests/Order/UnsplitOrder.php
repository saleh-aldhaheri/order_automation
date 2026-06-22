<?php

namespace App\Integrations\Shopee\Requests\Order;

use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Undo a split — return the order to a single package.
 *
 * Conditions: order_status = READY_TO_SHIP (no parcel shipped).
 */
class UnsplitOrder extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $orderSn,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/unsplit_order';
    }

    protected function defaultBody(): array
    {
        return [
            'order_sn' => $this->orderSn,
        ];
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
