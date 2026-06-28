<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\SearchPackageFilterData;
use App\Integrations\Shopee\Data\SearchPackageListData;
use App\Integrations\Shopee\Data\SearchPackageSortData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use stdClass;

/**
 * Find packages not yet shipped (preferred over get_shipment_list), with filters.
 */
class SearchPackageList extends ShopeeRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly int $pageSize,
        public readonly ?string $cursor = null,
        public readonly ?SearchPackageFilterData $filter = null,
        public readonly ?SearchPackageSortData $sort = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/search_package_list';
    }

    protected function defaultBody(): array
    {
        $filter = $this->filter ? $this->stripNull($this->filter->toArray()) : [];

        $body = [
            'filter' => empty($filter) ? new stdClass : $filter,
            'pagination' => $this->stripNull([
                'page_size' => $this->pageSize,
                'cursor' => $this->cursor,
            ]),
        ];

        if ($this->sort) {
            $body['sort'] = $this->stripNull($this->sort->toArray());
        }

        return $body;
    }


    public function toDto(Response $response): SearchPackageListData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        return SearchPackageListData::from(data_get($json, 'response', []));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripNull(array $data): array
    {
        return array_filter($data, static fn($value): bool => $value !== null);
    }
}
