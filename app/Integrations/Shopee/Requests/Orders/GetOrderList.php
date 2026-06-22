<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\GetOrderListData;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetOrderList extends Request
{

    public Method $method = Method::GET;

    public function __construct(
        public readonly string $timeRangeField,
        public readonly int $timeFrom,
        public readonly int $timeTo,
        public readonly int $pageSize = 50,
        public readonly ?string $cursor = null,
        public readonly ?ShopeeOrderStatusEnum $orderStatus = null,
        public readonly ?string $responseOptionalFields =  null,
        public readonly ?bool $requestOrderStatusPending = null,
        public readonly ?int $logisticsChannelId =  null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/get_order_list';
    }

    public function defaultQuery(): array
    {
        $query  = [
            'time_range_field' => $this->timeRangeField,
            'time_from' => $this->timeFrom,
            'time_to' => $this->timeTo,
            'page_size' => $this->pageSize,
        ];

        if ($this->orderStatus) {
            $query['order_status'] = $this->orderStatus->value;
        }

        if ($this->responseOptionalFields) {
            $query['response_optional_fields'] = $this->responseOptionalFields;
        }

        if ($this->requestOrderStatusPending) {
            $query['request_order_status_pending'] = $this->requestOrderStatusPending;
        }

        if ($this->logisticsChannelId) {
            $query['logistics_channel_id'] = $this->logisticsChannelId;
        }

        return $query;
    }

    public function createDtoFromResponse(Response $response): GetOrderListData
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        return GetOrderListData::from(data_get($json, 'response', []));
    }
}
