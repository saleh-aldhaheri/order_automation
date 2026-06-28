<?php

use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Resources\Authorization;
use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Authorization\GetAccessToken;
use App\Integrations\Shopee\Requests\Orders\GetOrderDetail;
use App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use App\Integrations\Shopee\Resources\Logistics;
use App\Integrations\Shopee\Resources\Orders as OrdersResource;

/**
 * Send a request through the connector and return the raw failed Response.
 *
 * tries=1 stops the retry loop. The connector uses AlwaysThrowOnErrors, so a
 * failed response throws a ShopeeException (which now extends Saloon's
 * RequestException and carries the Response) — catch it and hand back its
 * Response so callers can build an exception for handleRetry().
 */
function fakeFailedResponse(ShopeeClient $client, Request $request, int $status): Response
{
    $client->tries = 1;
    $client->withMockClient(new MockClient(['*' => MockResponse::make([], $status)]));

    try {
        return $client->send($request);
    } catch (ShopeeException $exception) {
        return $exception->getResponse();
    }
}

beforeEach(function() {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId =  config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = "fake";
    $this->shopId = "fake";
    $this->refreshToken  = "fake";

    $this->shopeeClient = new ShopeeClient(
        $this->partnerId,
        $this->partnerKey,
        $this->baseUrl
    );

    $this->shopeeAuthClient = new ShopeeClient(
        $this->partnerId,
        $this->partnerKey,
        $this->baseUrl,
        $this->accessToken,
        $this->shopId,
        $this->refreshToken,
    );

    $this->publicRquest = new GetAccessToken(
        "code",
        $this->partnerId,
        $this->shopId,
        "shop_id"
    );

    $this->privateRequest = new GetOrderDetail(
        ["order_id"]
    );
});

describe('create client', function() {

    it('create shopee client instance', function () {

        expect($this->shopeeClient)->toBeInstanceOf(ShopeeClient::class);
    });

    it('create shopee when optional fields passed', function (
        ?string $accessToken,
        ?string $shopId,
        ?string $refreshToken,
        ?Closure $fn
    ) {
        $client = new ShopeeClient(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: $accessToken,
            shopId: $shopId,
            refreshToken: $refreshToken,
            persistRefreshedToken: $fn
        );

        expect($client)->toBeInstanceOf(ShopeeClient::class);
    })->with([
        ['1', '2', '4', null],
        ['1', '2', null, fn () => null],
        ['1', null, '4', fn () => null],
        [null, '2', '4', fn () => null],
    ]);
});

describe('sign', function() {

    it('sign non public request correctly', function (string $accessToken, string $shopId, string $refreshToken, Closure $fn) {

        $path =  $this->baseUrl.'\test';
        $timestamp = time();

        $base = $this->partnerId . $path . $timestamp . $this->accessToken .$this->shopId;

        $hash =  hash_hmac('sha256', $base, $this->partnerKey);

        $clientHash = $this->shopeeAuthClient->sign($path, $timestamp, false);

        expect($hash)->toBe($clientHash);

    })->with([
        ['1', '2', '4', fn () => null],
    ]);

    it('should sign non public request correctly', function() {

        $path =  $this->baseUrl.'\test';
        $timestamp = time();

        $base = $this->partnerId . $path . $timestamp;

        $hash =  hash_hmac('sha256', $base, $this->partnerKey);
        $clientHash = $this->shopeeClient->sign($path, $timestamp, true);

        expect($hash)->toBe($clientHash);
    });

    it('should throw shopee exception when fields missing for signing private key', function() {

        $path =  $this->baseUrl.'\test';
        $timestamp = time();

        $this->shopeeClient->sign($path, $timestamp, false);

    })->throws(ShopeeException::class, "access token or account id missing for a shop API call");

});

describe('boot', function() {

    it('make sign the key and prepare query Parameter and payload correctly for private requests' , function() {
        $pending = $this->shopeeAuthClient->createPendingRequest($this->privateRequest);
        $query = $pending->query()->all();
        //the used request does not have payload GetOrderDetails Request

        expect($query)->toHaveKeys([
            'partner_id',
            'timestamp',
            'sign',
            'order_sn_list',
            'request_order_status_pending',
            'response_optional_filed'
        ]);
    });

   it('make sign the key and prepare query Parameter and payload correctly for public requests',function() {

        $pending = $this->shopeeClient->createPendingRequest($this->publicRquest);

        $query = $pending->query()->all();
        $payload = $pending->body()->all();

       expect($query)->toHaveKeys(['partner_id', 'timestamp', 'sign'])
           ->and($payload)->toHaveKeys(['partner_id', 'code', 'shop_id']);
   });

    it('throw shopee exception when not enable to make request',function() {

        $client = new ShopeeClient(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl
        );

        $client->createPendingRequest($this->privateRequest);

    })->throws(ShopeeException::class);
});

describe('refresh token', function() {
    it('throw shopee exception when the class refresh token not exist', function() {
        $newAccessToken = 'new-access-token';
        $newRefreshToken = 'new-refresh-token';

        $mockRequest = new \Saloon\Http\Faking\MockClient([
            \App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
                'access_token'  => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'expire_in' => 3600,
            ])
        ], 200);

        expect($this->shopeeAuthClient->accessToken)->toBe($this->accessToken)
            ->and($this->shopeeAuthClient->refreshToken)->toBe($this->refreshToken);

        $this->shopeeAuthClient->withMockClient($mockRequest);
        $this->shopeeAuthClient->refresh();

        expect($this->shopeeAuthClient->accessToken)->toBe($newAccessToken)
            ->and($this->shopeeAuthClient->refreshToken)->toBe($newRefreshToken);
    });

    it('should be able to persist the data using the closer', function() {
        $newAccessToken = 'new-access-token';
        $newRefreshToken = 'new-refresh-token';

        $mockRequest = new \Saloon\Http\Faking\MockClient([
            \App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
                'access_token'  => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'expire_in' => 3600,
            ])
        ], 200);

       $shop =  \App\Models\Shop::factory()->create();
       $oldAccessToken = data_get($shop->auth_configuration, 'auth.access_token.token');
       $oldRefreshToken = data_get($shop->auth_configuration, 'auth.refresh_token.token');
       $expiredRefresh  = data_get($shop->auth_configuration, 'auth.refresh_token.expired_in');
       $expiredAccess = data_get($shop->auth_configuration, 'auth.access_token.expired_in');

       $client = new ShopeeClient(
            partnerId: config('services.shopee.partner_id'),
            partnerKey: config('services.shopee.partner_key'),
            baseUrl: config('services.shopee.base_url'),
            accessToken: $oldAccessToken,
            shopId: $shop->external_shop_id,
            refreshToken: $oldRefreshToken,
            persistRefreshedToken: function($refreshData) use($shop) {
                $authConfiguration = $shop->auth_configuration;
                $authConfiguration['auth']['access_token']['token']       = $refreshData->accessToken;
                $authConfiguration['auth']['access_token']['expired_in']  = now()->addSeconds($refreshData->expireIn);
                $authConfiguration['auth']['refresh_token']['token']      = $refreshData->refreshToken;
                $authConfiguration['auth']['refresh_token']['expired_in'] = now()->addDays(30);
                $shop->auth_configuration = $authConfiguration;
                $shop->save();
            }
        );

       $client->withMockClient($mockRequest);
       $client->refresh();

       $shop->refresh();

       expect(data_get($shop->auth_configuration, 'auth.access_token.token'))->toBe($newAccessToken)
        ->and(data_get($shop->auth_configuration, 'auth.refresh_token.token'))->toBe($newRefreshToken)
       ->and(data_get($shop->auth_configuration, 'auth.access_token.expired_in'))->not->toBe($expiredAccess)
       ->and(data_get($shop->auth_configuration, 'auth.refresh_token.expired_in'))->not()->toBe($expiredRefresh);
    });
});

describe('retry', function() {
    it('retries the request up to the connector limit (3) then gives up on repeated 401s', function () {
        $mock = new MockClient([

            GetOrderDetail::class     => MockResponse::make(['error' => 'invalid_access_token'], 401),

            RefreshAccessToken::class => MockResponse::make([
                'access_token'  => 'fresh-access',
                'refresh_token' => 'fresh-refresh',
                'expire_in'     => 3600, // was `expire_In` — wrong key, never mapped to expireIn
            ], 200),
        ]);

        $this->shopeeAuthClient->withMockClient($mock);
        $this->shopeeAuthClient->retryInterval = 0;

        expect(fn () => $this->shopeeAuthClient->send(new GetOrderDetail(['order-id'])))
            ->toThrow(ShopeeException::class);

        $mock->assertSentCount(3, GetOrderDetail::class);
        $mock->assertSentCount(2, RefreshAccessToken::class);
    });

    it('returns true and triggers refresh on 401 with refresh token', function () {
        $request   = new GetOrderDetail(['id']);
        $exception = fakeFailedResponse($this->shopeeAuthClient, $request, 401)->toException();

        $this->shopeeAuthClient->withMockClient(new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'access_token'  => 'new-token',
                'refresh_token' => 'new-refresh',
                'expire_in'     => 3600,
            ], 200),
        ]));

        $result = $this->shopeeAuthClient->handleRetry($exception, $request);

        expect($result)->toBeTrue()
            ->and($this->shopeeAuthClient->accessToken)->toBe('new-token')
            ->and($this->shopeeAuthClient->refreshToken)->toBe('new-refresh');
    });

    it('should not retry if the request not from type not authorized', function (int $status) {
        $request   = new GetOrderDetail(['id']);
        $exception = fakeFailedResponse($this->shopeeAuthClient, $request, $status)->toException();


        $result = $this->shopeeAuthClient->handleRetry($exception, $request);

        expect($result)->toBeFalse()
            ->and($this->shopeeAuthClient->accessToken)->toBe($this->accessToken); // unchanged
    })->with([500, 422, 403, 404]);

    it('should not retry on a 401 when there is no refresh token', function () {
        $client = new ShopeeClient(
            $this->partnerId,
            $this->partnerKey,
            $this->baseUrl,
            accessToken: 'fake',
            shopId: 'fake',
            refreshToken: null, // nothing to refresh with
        );

        $request   = new GetOrderDetail(['id']);
        $exception = fakeFailedResponse($client, $request, 401)->toException();

        expect($client->handleRetry($exception, $request))->toBeFalse();
    });

    it('should not retry on a fatal (connection) error', function () {
        $request   = new GetOrderDetail(['id']);
        // FatalRequestException carries no HTTP response, so the 401 guard never
        // matches and handleRetry must bail out without attempting a refresh.
        $exception = new \Saloon\Exceptions\Request\FatalRequestException(
            new \Exception('Connection refused'),
            $this->shopeeAuthClient->createPendingRequest($request),
        );

        expect($this->shopeeAuthClient->handleRetry($exception, $request))->toBeFalse();
    });
});

describe('resource functions', function() {
    it('return correct resource type', function() {
        expect($this->shopeeAuthClient->authorization())->toBeInstanceOf(Authorization::class)
            ->and($this->shopeeAuthClient->order())->toBeInstanceOf(OrdersResource::class)
            ->and($this->shopeeAuthClient->logistic())->toBeInstanceOf(Logistics::class);
    });
});

it('throw Shopee Exception when http request fail',  function() {
     $mockRequest =  new MockClient([
         GetOrderDetail::class => MockResponse::make([
             'message' => 'server side error',
         ],500)
     ]) ;

     $this->shopeeAuthClient->withMockClient($mockRequest);

     $this->shopeeAuthClient->order()->getOrderDetail(['id']);

})->throws(ShopeeException::class);
