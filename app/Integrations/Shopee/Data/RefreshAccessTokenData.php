<?php

namespace App\Integrations\Shopee\Data;

use App\Integrations\Shopee\Resources\Authorization;
use App\Services\Integrations\ShopeeService;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee `auth/access_token/get` (Refresh Access Token) response — a faithful
 * mirror of Shopee's payload. Vendor language only: this DTO carries no
 * application concepts. Translation into the app's `auth_configuration` shape
 * happens in {@see ShopeeService}.
 *
 * On error Shopee still returns 200 with a non-empty `error` and empty token
 * fields. That error envelope is screened in
 * {@see Authorization::refreshAccessToken()}
 * BEFORE this DTO is hydrated, so this DTO only ever models a *successful*
 * refresh: the token fields below are required (their absence is a real bug,
 * not a valid state). Only the grant-dependent ids stay nullable.
 */
#[MapName(SnakeCaseMapper::class)]
class RefreshAccessTokenData extends Data
{
    /**
     * @param  string  $accessToken  required on success
     * @param  string  $refreshToken  required on success
     * @param  int  $expireIn  required on success (seconds)
     * @param  int|null  $shopId  present when refreshing a shop_id grant
     * @param  int|null  $merchantId  present when refreshing a merchant grant
     * @param  array<int, int>|null  $supplierIdList  present when auth_type=supplier
     * @param  array<int, int>|null  $userIdList  present when auth_type=user
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expireIn,
        public ?int $shopId = null,
        public ?int $merchantId = null,
        public ?int $partnerId = null,
        public ?string $requestId = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?array $supplierIdList = null,
        public ?array $userIdList = null,
    ) {}
}
