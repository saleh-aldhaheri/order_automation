<?php

namespace App\Integrations\Shopee\Requests\Logistics\Document;

use App\Integrations\Shopee\Data\ShippingDocumentOrderData;
use App\Integrations\Shopee\Enums\ShopeeShippingDocumentTypeEnum;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Download the waybill file. Only after get_shipping_document_result status = READY.
 *
 * On success the body is the file itself (mostly PDF; TW C2C -> HTML; thermal
 * setting -> ZIP — handle non-PDF). On failure the body is JSON
 * { error, message, request_id }. `shippingDocumentType` is a top-level param
 * (same type used at create), NOT per order entry.
 */
class DownloadShippingDocument extends Request
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, ShippingDocumentOrderData>  $orderList
     */
    public function __construct(
        public readonly array $orderList,
        public readonly ?ShopeeShippingDocumentTypeEnum $shippingDocumentType = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/download_shipping_document';
    }

    protected function defaultBody(): array
    {
        $body = [
            'order_list' => array_map(
                static fn(ShippingDocumentOrderData $order): array => array_filter(
                    $order->toArray(),
                    static fn($value): bool => $value !== null,
                ),
                $this->orderList,
            ),
        ];

        if ($this->shippingDocumentType) {
            // Cast the enum to its string value for the outgoing request.
            $body['shipping_document_type'] = $this->shippingDocumentType->value;
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): string
    {
        $body = $response->body();

        $decoded = json_decode($body, true);

        if (is_array($decoded) && ! empty($decoded['error'])) {
            throw new RuntimeException($decoded['message'] ?? $decoded['error']);
        }

        return $body;
    }
}
