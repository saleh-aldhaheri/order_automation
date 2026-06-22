<?php

namespace App\Data\Integrations\Requests;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class HandleCallbackRequest extends Data
{

    public function __construct(
        public Request $request,
    ) {}

    public function toShopee(): array
    {
        $data = $this->request->query();
        $code = data_get($data, "code");
        $shopId = data_get($data, "shop_id");
        $state = data_get($data, "state");
        $mainAccountId = data_get($data, 'main_account_id');

        if (! $code || ! $state || (! $shopId && ! $mainAccountId)) {
            throw new \RuntimeException("Invalid shoppe callback request");
        }

        return [
            'code' => $code,
            'shop_id' =>  $shopId,
            'state' =>  $state,
            'main_account_id' => $mainAccountId
        ];
    }
}
