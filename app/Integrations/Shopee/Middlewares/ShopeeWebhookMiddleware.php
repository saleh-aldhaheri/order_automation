<?php

namespace App\Integrations\Shopee\Middlewares;

use App\Integrations\Shopee\Exceptions\ShopeeException;
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

        $baseString = $url.'|'.$rawBody;

        $expected = hash_hmac('sha256', $baseString, config('services.shopee.partner_key'));

        if (! $authorization || ! hash_equals($expected, $authorization)) {
            throw new ShopeeException('Invalid Shopee push signature', 401);
        }

        return $next($request);
    }
}
