<?php

namespace App\Integrations\Shopee\Requests;

use App\Data\Integrations\Shopee\GetOrderDetailsData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetOrderDetails extends Request
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
     *
     * @param  ?string  $responseOptionalFiled
     *     Comma-separated list of optional response fields to include. If an object
     *     field is given, all of its sub-params are included automatically.
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

    public function createDtoFromResponse(Response $response): mixed
    {
        $ordersList = data_get($response->json()['response'], 'order_list');
        $ordersList = $ordersList->map(fn($order) => GetOrderDetailsData::from($order));
        return $ordersList;
    }
}
