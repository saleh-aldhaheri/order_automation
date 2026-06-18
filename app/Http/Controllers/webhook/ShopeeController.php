<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\Integrations\ShopeeEventsEnum;
use App\Events\Integrations\ShopeeWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShopeeController
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->json()->all();

        $code   = data_get($payload, 'code');

        $type = ShopeeEventsEnum::tryFrom((int) $code);

        if ($type) {
            ShopeeWebhookEvent::dispatch($payload, $code);
        }

        return response('', 200);
    }
}
