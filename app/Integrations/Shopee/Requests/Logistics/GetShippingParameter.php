<?php

namespace App\Integrations\Shopee\Requests\Logistics;

use App\Integrations\Shopee\Data\GetShippingParameterData;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetShippingParameter extends Request
{
    protected Method $method = Method::GET;

    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $orderSn,
        public readonly ?string $packageNumber = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/logistics/get_shipping_parameter';
    }

    public function defaultQuery(): array
    {
        $query  = [
            'order_sn' => $this->orderSn
        ];

        if ($this->packageNumber) {
            $query['package_number'] = $this->packageNumber;
        }

        return $query;
    }

    public function createDtoFromResponse(Response $response): GetShippingParameterData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        return GetShippingParameterData::from(data_get($json, 'response', []));
    }
}
