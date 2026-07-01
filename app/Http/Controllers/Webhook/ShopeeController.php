<?php

namespace App\Http\Controllers\Webhook;

use App\Integrations\Shopee\Enums\ShopeeEventsEnum;
use App\Integrations\Shopee\Events\ShopeeEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShopeeController
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->json()->all();

        $code = data_get($payload, 'code');

        $eventType = ShopeeEventsEnum::tryFrom((int) $code);

        if ($eventType) {
            ShopeeEvent::dispatch($payload, $eventType);
        }

        return response('', 200);
    }
}
