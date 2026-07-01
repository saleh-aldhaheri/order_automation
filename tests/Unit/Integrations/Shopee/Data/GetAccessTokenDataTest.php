<?php

use App\Integrations\Shopee\Data\GetAccessTokenData;

it('creates the DTO via constructor', function () {

    $data = new GetAccessTokenData(
        accessToken: 'acc_123',
        refreshToken: 'ref_456',
        expireIn: 3600,
        requestId: 'req_001',
        error: null,
        message: null,
        merchantIdList: [1, 2],
        shopIdList: [10, 20],
        supplierIdList: null,
        userIdList: null,
    );

    expect($data->accessToken)->toBe('acc_123')
        ->and($data->refreshToken)->toBe('ref_456')
        ->and($data->expireIn)->toBe(3600)
        ->and($data->requestId)->toBe('req_001')
        ->and($data->merchantIdList)->toBe([1, 2])
        ->and($data->shopIdList)->toBe([10, 20])
        ->and($data->supplierIdList)->toBeNull()
        ->and($data->userIdList)->toBeNull();
});

it('hydrates from snake_case and maps correctly', function () {

    $data = GetAccessTokenData::from([
        'access_token' => 'acc_123',
        'refresh_token' => 'ref_456',
        'expire_in' => 3600,
        'request_id' => 'req_001',
        'error' => 'INVALID_AUTH',
        'message' => 'Auth failed',
        'merchant_id_list' => [1, 2],
        'shop_id_list' => [10, 20],
        'supplier_id_list' => [100],
        'user_id_list' => [999],
    ]);

    expect($data->accessToken)->toBe('acc_123')
        ->and($data->refreshToken)->toBe('ref_456')
        ->and($data->expireIn)->toBe(3600)
        ->and($data->requestId)->toBe('req_001')
        ->and($data->error)->toBe('INVALID_AUTH')
        ->and($data->message)->toBe('Auth failed')
        ->and($data->merchantIdList)->toBe([1, 2])
        ->and($data->shopIdList)->toBe([10, 20])
        ->and($data->supplierIdList)->toBe([100])
        ->and($data->userIdList)->toBe([999]);
});
