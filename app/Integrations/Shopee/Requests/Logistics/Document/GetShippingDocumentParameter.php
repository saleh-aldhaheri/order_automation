<?php

namespace App\Integrations\Shopee\Requests\Logistics\Document;

use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Data\ShippingDocumentParameterResultData;
use Illuminate\Support\Collection;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Get the selectable + suggested document type for each order before creating
 * the waybill. Runs per package for split orders (one entry per package).
 */
class GetShippingDocumentParameter extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, ShippingDocumentOrderData>  $orderList  Limit [1,50].
     */
    public function __construct(
        public readonly array $orderList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/get_shipping_document_parameter';
    }

    protected function defaultBody(): array
    {
        return [
            'order_list' => array_map(
                static fn(ShippingDocumentOrderData $order): array => array_filter(
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

        return ShippingDocumentParameterResultData::collect($results, Collection::class);
    }
}
