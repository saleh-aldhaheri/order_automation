<?php

use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken;
use App\Integrations\Shopee\ShopeeClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId = config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->shopId = 1;
    $this->expireIn = time();
    $this->idType = 'shop_id';
    $this->refreshToken = bin2hex(random_bytes(16));

    $this->request = new RefreshAccessToken(
        refreshToken: $this->refreshToken,
        partnerId: $this->partnerId,
        shopId: $this->shopId,
    );

    $this->shopeeClient = new ShopeeClient(
        partnerId: $this->partnerId,
        partnerKey: $this->partnerKey,
        baseUrl: $this->baseUrl,
        shopId: $this->shopId,
        refreshToken: $this->refreshToken,
    );
});

describe('request', function () {
    it('uses every constructor argument to prepare the payload', function () {
        expect($this->request->body()->all())->toBe([
            'refresh_token' => $this->refreshToken,
            'partner_id' => (int) $this->partnerId,
            $this->idType => $this->shopId,
        ]);
    });

    it('uses the correct endpoint for the request', function () {
        expect($this->request->resolveEndpoint())->toBe('/api/v2/auth/access_token/get');
    });
});

describe('response', function () {
    it('casts the full dto, including the optional fields, from the response', function () {

        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => 'token',
                'access_token' => 'token',
                'expire_in' => $this->expireIn,
                'shop_id' => $this->shopId,
                'merchant_id' => 2,
                'partner_id' => (int) $this->partnerId,
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'supplier_id_list' => [1, 2],
                'user_id_list' => [3, 4],
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->authorization()->refreshAccessToken();

        expect($response)->toBeInstanceOf(RefreshAccessTokenData::class)
            ->and($response->accessToken)->toBe('token')
            ->and($response->refreshToken)->toBe('token')
            ->and($response->expireIn)->toBe($this->expireIn)
            ->and($response->shopId)->toBe($this->shopId)
            ->and($response->merchantId)->toBe(2)
            ->and($response->partnerId)->toBe((int) $this->partnerId)
            ->and($response->requestId)->toBe('request-id')
            ->and($response->error)->toBe('')
            ->and($response->message)->toBe('')
            ->and($response->supplierIdList)->toBe([1, 2])
            ->and($response->userIdList)->toBe([3, 4]);
    });

    it('casts the dto when only the required fields are returned', function () {

        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => 'token',
                'access_token' => 'token',
                'expire_in' => $this->expireIn,
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->authorization()->refreshAccessToken();

        expect($response)->toBeInstanceOf(RefreshAccessTokenData::class)
            ->and($response->accessToken)->toBe('token')
            ->and($response->refreshToken)->toBe('token')
            ->and($response->expireIn)->toBe($this->expireIn)
            ->and($response->shopId)->toBeNull()
            ->and($response->merchantId)->toBeNull()
            ->and($response->partnerId)->toBeNull()
            ->and($response->requestId)->toBeNull()
            ->and($response->error)->toBeNull()
            ->and($response->message)->toBeNull()
            ->and($response->supplierIdList)->toBeNull()
            ->and($response->userIdList)->toBeNull();
    });

    it('throws a ShopeeException when Shopee returns an error', function () {
        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'refresh_token' => 'token',
                'access_token' => 'token',
                'expire_in' => $this->expireIn,
                'error' => 'an error returned from shopee',
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->authorization()->refreshAccessToken();
    })->throws(ShopeeException::class);

    it('throws a ShopeeException when the casting fails', function () {
        $requestMock = new MockClient([
            RefreshAccessToken::class => MockResponse::make([
                'expire_in' => $this->expireIn,
            ]),
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);

        $this->shopeeClient->authorization()->refreshAccessToken();
    })->throws(ShopeeException::class);
});
