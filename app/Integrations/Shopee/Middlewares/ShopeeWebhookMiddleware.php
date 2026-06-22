<?php

namespace App\Integrations\Shopee\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopeeWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');
        $rawBody = $request->getContent();
        $url = $request->fullUrl();

        $baseString = $url . '|' . $rawBody;

        $expected = hash_hmac('sha256', $baseString, config('services.shopee.partner_key'));

        if (! $authorization || ! hash_equals($expected, $authorization)) {
            abort(401, 'Invalid Shopee push signature');
        }

        return $next($request);
    }
}
