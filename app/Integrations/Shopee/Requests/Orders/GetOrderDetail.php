<?php

namespace App\Integrations\Shopee\Requests\Orders;

use App\Integrations\Shopee\Data\GetOrderDetailsData;
use Illuminate\Support\Collection;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetOrderDetail extends Request
{
    protected Method $method = Method::GET;

    public bool $isPublic = false;

    private const OPTIONAL_FIELDS = [
        'buyer_user_id',
        'buyer_username',
        'estimated_shipping_fee',
        'recipient_address',
        'actual_shipping_fee',
        'goods_to_declare',
        'note',
        'note_update_time',
        'item_list',
        'pay_time',
        'dropshipper',
        'dropshipper_phone',
        'split_up',
        'buyer_cancel_reason',
        'cancel_by',
        'cancel_reason',
        'actual_shipping_fee_confirmed',
        'buyer_cpf_id',
        'fulfillment_flag',
        'pickup_done_time',
        'package_list',
        'shipping_carrier',
        'payment_method',
        'total_amount',
        'invoice_data',
        'order_chargeable_weight_gram',
        'return_request_due_date',
        'edt',
        'payment_info',
        'international_label',
    ];

    /**
     * @param  array  $orderSnList
     *     One or more order_sn values joined by commas. Limit [1,50]. Required.
     *
     * @param  ?bool  $requestOrderStatusPending
     *     Migration-period compatibility flag. Sending true makes the API support
     *     the PENDING status and return pending_terms; sending false (or omitting it)
     *     falls back to the old logic.
     */
    public function __construct(
        public array $orderSnList,
        public ?bool $requestOrderStatusPending = false,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/order/get_order_detail';
    }

    protected function defaultQuery(): array
    {
        return [
            'order_sn_list' => implode(',', $this->orderSnList),
            'request_order_status_pending' => $this->requestOrderStatusPending,
            'response_optional_filed' => implode(',', self::OPTIONAL_FIELDS)
        ];
    }

    /**
     * Inbound boundary: Shopee `response.order_list` -> faithful vendor DTOs.
     *
     * Translation into the application's OrderResponse happens in
     * {@see \App\Services\Integrations\ShopeeService} — this layer only speaks
     * Shopee's language.
     *
     * @return \Illuminate\Support\Collection<int, GetOrderDetailsData>
     */
    public function createDtoFromResponse(Response $response): Collection
    {
        $json = $response->json();

        if (! empty($json['error'])) {
            throw new RuntimeException($json['error']);
        }

        $orders = data_get($json, 'response.order_list', []);

        return GetOrderDetailsData::collect($orders, Collection::class);
    }
}
