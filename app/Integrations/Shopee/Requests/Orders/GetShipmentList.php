<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\GetShipmentListData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/***
 *  not this endpoint might get deprecated
 *  suggest to use search package list instead
 */
class GetShipmentList extends ShopeeRequest
{
    public Method $method = Method::GET;

    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly int $pageSize,
        public readonly ?string $cursor = null
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/api/v2/order/get_shipment_list";
    }

    public function defaultQuery(): array
    {
        $query = [
            'page_size' =>  $this->pageSize
        ];

        if ($this->cursor) {
            $query['cursor'] = $this->cursor;
        }

        return $query;
    }

    public function toDto(Response $response): GetShipmentListData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new ShopeeException($json['error']);
        }

        return GetShipmentListData::from(data_get($json, 'response', []));
    }
}
