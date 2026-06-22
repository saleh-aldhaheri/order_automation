<?php

namespace App\Integrations\Shopee\Requests\Logistics;

use App\Integrations\Shopee\Data\GetTrackingNumberData;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Get the tracking number (REQUIRED for creating the waybill).
 *
 * The number comes from the 3PL and may not be ready yet — the response can be
 * empty. Poll every 5 min until present, or listen for order_trackingno_push.
 */
class GetTrackingNumber extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $orderSn,
        public readonly ?string $packageNumber = null,
        public readonly ?string $responseOptionalFields = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/get_tracking_number';
    }

    public function defaultQuery(): array
    {
        $query = [
            'order_sn' => $this->orderSn,
        ];

        if ($this->packageNumber) {
            $query['package_number'] = $this->packageNumber;
        }

        if ($this->responseOptionalFields) {
            $query['response_optional_fields'] = $this->responseOptionalFields;
        }

        return $query;
    }

    public function createDtoFromResponse(Response $response): GetTrackingNumberData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        return GetTrackingNumberData::from(data_get($json, 'response', []));
    }
}
