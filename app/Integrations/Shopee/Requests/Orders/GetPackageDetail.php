<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\PackageDetailData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Get package details for a set of package numbers.
 */
class GetPackageDetail extends ShopeeRequest
{
    protected Method $method = Method::GET;

    /**
     * @param  array<int, string>  $packageNumberList
     *     Set of package_number values, comma-joined for the API. Limit [1,50].
     */
    public function __construct(
        public readonly array $packageNumberList,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/get_package_detail';
    }

    public function defaultQuery(): array
    {
        return [
            'package_number_list' => implode(',', $this->packageNumberList),
        ];
    }

    /**
     * Inbound boundary: Shopee `response.package_list` -> faithful vendor DTOs.
     *
     * @return \Illuminate\Support\Collection<int, PackageDetailData>
     */
    public function toDto(Response $response): Collection
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        $packages = data_get($json, 'response.package_list', []);

        return PackageDetailData::collect($packages, Collection::class);
    }
}
