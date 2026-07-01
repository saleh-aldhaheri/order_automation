<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\SplitOrderData;
use App\Integrations\Shopee\Data\SplitOrderPackageData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Split one order into multiple packages.
 *
 * Conditions: order_status = READY_TO_SHIP; include ALL items in one request;
 * max parcels 30 (TW) / 5 (other); same item+model can't be split unless
 * whitelisted; needs split permission.
 */
class SplitOrder extends ShopeeRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, SplitOrderPackageData>  $packageList
     */
    public function __construct(
        public readonly string $orderSn,
        public readonly array $packageList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/split_order';
    }

    protected function defaultBody(): array
    {
        return [
            'order_sn' => $this->orderSn,
            'package_list' => array_map(
                static fn (SplitOrderPackageData $package): array => $package->toArray(),
                $this->packageList,
            ),
        ];
    }

    public function toDto(Response $response): SplitOrderData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        return SplitOrderData::from(data_get($json, 'response', []));
    }
}
