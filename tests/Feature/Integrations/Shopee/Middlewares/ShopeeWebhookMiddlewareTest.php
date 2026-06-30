<?php

use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Middlewares\ShopeeWebhookMiddleware;

beforeEach(function() {
    $this->requestContent = ["test" => "content"];
    $this->fullUrl = "https://testing.com";
    $this->rawBody = json_encode($this->requestContent);
    $this->baseString = $this->fullUrl . '|' . $this->rawBody;
    $this->signature = hash_hmac('sha256', $this->baseString, config('services.shopee.partner_key'));
    $this->middleware = new ShopeeWebhookMiddleware();
});

it('allows the request to pass when a correct signature is provided', function () {

    $request = Request::create(
        $this->fullUrl,
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $this->rawBody
    );

    $request->headers->set('Authorization', $this->signature);

    $response = $this->middleware->handle($request, function() {
        return response()->json(['message' => 'success'], 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('throws a ShopeeException with a 401 status code when the signature is corrupted', function(?string $signature) {

    $request = Request::create(   $this->fullUrl,
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $this->rawBody
    );
    $request->headers->set('Authorization', $signature);

    $this->middleware->handle($request, function() {
        return response()->json(['message' => 'success'], 200);
    });

})->throws( ShopeeException::class)->with(["fake", "", null]);

it('allows a correct signature to pass when the middleware is attached to the route', function() {
    $fullUrl = url('/webhook/shopee');
    $baseString = $fullUrl . '|' . $this->rawBody;
    $signature = hash_hmac('sha256', $baseString, config('services.shopee.partner_key'));

    $response = $this->withHeader('Authorization', $signature)
        ->postJson('/webhook/shopee', $this->requestContent);

    expect($response->getStatusCode())->toBe(200);
});

it('prevents the request from passing when a wrong signature is provided', function() {
    $fullUrl = url('/webhook/shopee');
    $baseString = $fullUrl . '|' . $this->rawBody;
    $signature = hash_hmac('sha256', $baseString, "wrong-key");

    $response = $this->withHeader('Authorization', $signature)
        ->postJson('/webhook/shopee', $this->requestContent);

    expect($response->getStatusCode())->toBe(401);
});
