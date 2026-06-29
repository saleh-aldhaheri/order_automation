<?php


use App\Integrations\Shopee\Requests\Authorization\GetAccessToken;
use App\Integrations\Shopee\ShopeeClient;
use App\Integrations\Shopee\Data\GetAccessTokenData;
use App\Integrations\Shopee\Exceptions\ShopeeException;

beforeEach(function () {
   $this->partnerKey = config('services.shopee.partner_key');
   $this->partnerId =  config('services.shopee.partner_id');
   $this->baseUrl = config('services.shopee.base_url');
   $this->code = 'code';
   $this->shopId = 1;
   $this->expireIn = time();
   $this->idType = 'shop_id';

   $this->request = new GetAccessToken(
        code: $this->code,
        partnerId: $this->partnerId,
        shopId: $this->shopId,
        idType: $this->idType,
    );

   $this->shopeeClient = new ShopeeClient(
       $this->partnerId,
       $this->partnerKey,
       $this->baseUrl
   );
});

describe('request' , function() {
  it('uses every constructor argument to prepare the payload', function() {
      expect($this->request->body()->all())->toBe([
          'code' => $this->code,
          'partner_id' => (int) $this->partnerId,
          $this->idType => $this->shopId,
      ]);
  });

  it('uses the correct endpoint for the request', function() {
      expect($this->request->resolveEndpoint())->toBe( '/api/v2/auth/token/get');
  });
});

describe('response', function() {
  it('casts the full dto, including the optional fields, from the response', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
                'refresh_token' => 'token',
                'access_token' => 'token',
                'expire_in' => $this->expireIn,
                'request_id' => 'request-id',
                'error' => '',
                'message' => '',
                'merchant_id_list' => [1, 2],
                'shop_id_list' => [3, 4],
                'supplier_id_list' => [5, 6],
                'user_id_list' => [7, 8],
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->authorization()->getAccessToken(
              $this->code,
              $this->shopId,
              $this->idType
          );

        expect($response)->toBeInstanceOf(GetAccessTokenData::class)
        ->and($response->accessToken)->toBe('token')
        ->and($response->refreshToken)->toBe('token')
        ->and($response->expireIn)->toBe($this->expireIn)
        ->and($response->requestId)->toBe('request-id')
        ->and($response->error)->toBe('')
        ->and($response->message)->toBe('')
        ->and($response->merchantIdList)->toBe([1, 2])
        ->and($response->shopIdList)->toBe([3, 4])
        ->and($response->supplierIdList)->toBe([5, 6])
        ->and($response->userIdList)->toBe([7, 8]);
  });

  it('casts the dto when only the required fields are returned', function() {

        $requestMock  = new \Saloon\Http\Faking\MockClient([
            GetAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
                'refresh_token' => 'token',
                'access_token' => 'token',
                'expire_in' => $this->expireIn,
            ])
        ], 200);

        $this->shopeeClient->withMockClient($requestMock);
        $response = $this->shopeeClient->authorization()->getAccessToken(
              $this->code,
              $this->shopId,
              $this->idType
          );

        expect($response)->toBeInstanceOf(GetAccessTokenData::class)
        ->and($response->accessToken)->toBe('token')
        ->and($response->refreshToken)->toBe('token')
        ->and($response->expireIn)->toBe($this->expireIn)
        ->and($response->requestId)->toBeNull()
        ->and($response->error)->toBeNull()
        ->and($response->message)->toBeNull()
        ->and($response->merchantIdList)->toBeNull()
        ->and($response->shopIdList)->toBeNull()
        ->and($response->supplierIdList)->toBeNull()
        ->and($response->userIdList)->toBeNull();
  });

  it('throws a ShopeeException when Shopee returns an error', function () {
      $requestMock  = new \Saloon\Http\Faking\MockClient([
          GetAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
              'refresh_token' => 'token',
              'access_token' => 'token',
              'expire_in' => $this->expireIn,
              'error' => 'an error returned from shopee'
          ])
      ], 200);

      $this->shopeeClient->withMockClient($requestMock);

      $this->shopeeClient->authorization()->getAccessToken(
          $this->code,
          $this->shopId,
          $this->idType
      );
  })->throws(ShopeeException::class);

  it('throws a ShopeeException when the casting fails', function() {
      $requestMock  = new \Saloon\Http\Faking\MockClient([
          GetAccessToken::class =>  \Saloon\Http\Faking\MockResponse::make([
              'expire_in' => $this->expireIn,
          ])
      ], 200);

      $this->shopeeClient->withMockClient($requestMock);

      $this->shopeeClient->authorization()->getAccessToken(
          $this->code,
          $this->shopId,
          $this->idType
      );
  })->throws(ShopeeException::class);
});
