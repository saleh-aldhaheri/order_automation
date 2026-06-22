<?php

namespace App\Integrations\Shopee\Requests\Logistics\Document;

use App\Integrations\Shopee\Data\CreateShippingDocumentOrderData;
use App\Integrations\Shopee\Data\CreateShippingDocumentResultData;
use Illuminate\Support\Collection;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Start the AWB (waybill) task. Only after a tracking number exists, and before
 * LOGISTICS_PICKUP_DONE. Runs per package for split orders (one entry per package).
 */
class CreateShippingDocument extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, CreateShippingDocumentOrderData>  $orderList  Limit [1,50].
     */
    public function __construct(
        public readonly array $orderList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/create_shipping_document';
    }

    protected function defaultBody(): array
    {
        return [
            'order_list' => array_map(
                static fn(CreateShippingDocumentOrderData $order): array => array_filter(
                    $order->toArray(),
                    static fn($value): bool => $value !== null,
                ),
                $this->orderList,
            ),
        ];
    }

    public function createDtoFromResponse(Response $response): Collection
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        $results = data_get($json, 'response.result_list', []);

        return CreateShippingDocumentResultData::collect($results, Collection::class);
    }
}
