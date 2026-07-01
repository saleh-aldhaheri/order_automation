<?php

use App\Integrations\Shopee\Data\RefreshAccessTokenData;

it('creates refresh access token DTO via constructor', function () {

    $data = new RefreshAccessTokenData(
        accessToken: 'acc_123',
        refreshToken: 'ref_456',
        expireIn: 7200,
        shopId: 1001,
        merchantId: 2002,
        partnerId: 3003,
        requestId: 'req_001',
        error: null,
        message: null,
        supplierIdList: [1, 2, 3],
        userIdList: [9, 8, 7],
    );

    expect($data->accessToken)->toBe('acc_123')
        ->and($data->refreshToken)->toBe('ref_456')
        ->and($data->expireIn)->toBe(7200)
        ->and($data->shopId)->toBe(1001)
        ->and($data->merchantId)->toBe(2002)
        ->and($data->partnerId)->toBe(3003)
        ->and($data->requestId)->toBe('req_001')
        ->and($data->supplierIdList)->toBe([1, 2, 3])
        ->and($data->userIdList)->toBe([9, 8, 7]);
});

it('hydrates snake_case response into DTO correctly', function () {

    $data = RefreshAccessTokenData::from([
        'access_token' => 'acc_123',
        'refresh_token' => 'ref_456',
        'expire_in' => 7200,
        'shop_id' => 1001,
        'merchant_id' => 2002,
        'partner_id' => 3003,
        'request_id' => 'req_001',
        'error' => 'NONE',
        'message' => 'OK',
        'supplier_id_list' => [1, 2, 3],
        'user_id_list' => [9, 8, 7],
    ]);

    expect($data->accessToken)->toBe('acc_123')
        ->and($data->refreshToken)->toBe('ref_456')
        ->and($data->expireIn)->toBe(7200)
        ->and($data->shopId)->toBe(1001)
        ->and($data->merchantId)->toBe(2002)
        ->and($data->partnerId)->toBe(3003)
        ->and($data->requestId)->toBe('req_001')
        ->and($data->error)->toBe('NONE')
        ->and($data->message)->toBe('OK')
        ->and($data->supplierIdList)->toBe([1, 2, 3])
        ->and($data->userIdList)->toBe([9, 8, 7]);
});
