<?php

use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use App\Integrations\Shopee\Requests\Authorization\GetAccessToken;
use App\Integrations\Shopee\Exceptions\ShopeeException;
use App\Integrations\Shopee\Data\GetAccessTokenData;

beforeEach(function() {
    $this->partnerKey = config('services.shopee.partner_key');
    $this->partnerId =  config('services.shopee.partner_id');
    $this->baseUrl = config('services.shopee.base_url');
    $this->accessToken = "fake";
    $this->shopId = "fake";
    $this->refreshToken  = "fake";

    $this->shopeeClient = new ShopeeClient (
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
});

it('should be instance of Resource', function () {
  $authResource = $this->shopeeAuthClient->authorization();

  expect($authResource)->toBeInstanceOf(App\Integrations\Shopee\Resource::class)
      ->toHaveProperty('connector');
});

describe('refresh access token', function() {
    it('should call refresh token request correctly and return RefreshAccessTokenData', function () {

        $mockRequest = new MockClient([
            RefreshAccessToken::class => MockResponse::make( [
                "access_token" => "fake",
                "refresh_token" => "fake",
                "expire_in" => time()
            ])
        ]);

        $this->shopeeAuthClient->withMockClient($mockRequest);

        $response =  $this->shopeeAuthClient->authorization()->refreshAccessToken();

        expect($response->accessToken)->toBe("fake")
            ->and($response->refreshToken)->toBe('fake')
            ->and($response)->toBeInstanceOf(RefreshAccessTokenData::class);
    });

    it('throw exception when casting failed', function () {

        $mockRequest = new MockClient([
            RefreshAccessToken::class => MockResponse::make( [
                "foo" => "wrong response"
            ])
        ]);

        $this->shopeeAuthClient->withMockClient($mockRequest);

        $this->shopeeAuthClient->authorization()->refreshAccessToken();

    })->throws(ShopeeException::class);
});



describe('get access token', function() {
    it('should call refresh token request correctly and return RefreshAccessTokenData', function () {

        $mockRequest = new MockClient([
            GetAccessToken::class => MockResponse::make( [
                "access_token" => "fake",
                "refresh_token" => "fake",
                "expire_in" => time()
            ])
        ]);

        $this->shopeeAuthClient->withMockClient($mockRequest);

        $response =  $this->shopeeAuthClient->authorization()->getAccessToken('code', 'account_id', 'shop_id');

        expect($response->accessToken)->toBe("fake")
            ->and($response->refreshToken)->toBe('fake')
            ->and($response)->toBeInstanceOf(GetAccessTokenData::class);
    });

    it('throw exception when casting failed', function () {

        $mockRequest = new MockClient([
            GetAccessToken::class => MockResponse::make( [
                "foo" => "wrong response"
            ])
        ]);

        $this->shopeeAuthClient->withMockClient($mockRequest);

       $this->shopeeAuthClient->authorization()->getAccessToken('code', 'account_id', 'shop_id');

    })->throws(ShopeeException::class);
});

