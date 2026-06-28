<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Undo a split — return the order to a single package.
 *
 * Conditions: order_status = READY_TO_SHIP (no parcel shipped).
 */
class UnsplitOrder extends ShopeeRequest implements HasBody
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

    public function toDto(Response $response): bool
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        return true;
    }
}
