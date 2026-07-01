<?php

namespace App\Integrations\Shopee\Requests\Logistics;

use App\Integrations\Shopee\Data\GetShippingParameterData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class GetShippingParameter extends ShopeeRequest
{
    protected Method $method = Method::GET;

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
        $query = [
            'order_sn' => $this->orderSn,
        ];

        if ($this->packageNumber) {
            $query['package_number'] = $this->packageNumber;
        }

        return $query;
    }

    public function toDto(Response $response): GetShippingParameterData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        return GetShippingParameterData::from(data_get($json, 'response', []));
    }
}
