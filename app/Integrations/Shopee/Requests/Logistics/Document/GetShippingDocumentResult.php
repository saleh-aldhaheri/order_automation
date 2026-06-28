<?php

namespace App\Integrations\Shopee\Requests\Logistics\Document;

use App\Integrations\Shopee\Data\GetShippingDocumentResultOrderData;
use App\Integrations\Shopee\Data\ShippingDocumentResultData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Poll the AWB (waybill) task status — downloadable only when status = READY.
 *
 * Status may be PROCESSING: poll until READY (or FAILED), or listen for the
 * shipping_document_status_push (code 15) webhook. Runs per package for splits.
 */
class GetShippingDocumentResult extends ShopeeRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, GetShippingDocumentResultOrderData>  $orderList  Limit [1,50].
     */
    public function __construct(
        public readonly array $orderList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/get_shipping_document_result';
    }

    protected function defaultBody(): array
    {
        return [
            'order_list' => array_map(
                static fn(GetShippingDocumentResultOrderData $order): array => array_filter(
                    $order->toArray(),
                    static fn($value): bool => $value !== null,
                ),
                $this->orderList,
            ),
        ];
    }

    public function toDto(Response $response): Collection
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        $results = data_get($json, 'response.result_list', []);

        return ShippingDocumentResultData::collect($results, Collection::class);
    }
}
