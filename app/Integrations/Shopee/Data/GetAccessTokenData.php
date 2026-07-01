<?php

namespace App\Integrations\Shopee\Data;

use App\Integrations\Shopee\Resources\Authorization;
use App\Services\Integrations\ShopeeService;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee `auth/token/get` (Get Access Token) response — a faithful mirror of
 * Shopee's payload. Vendor language only: this DTO carries no application
 * concepts. Translation into the app's GetTokenResponseData happens in
 * {@see ShopeeService}.
 *
 * On error Shopee still returns 200 with a non-empty `error` and empty token
 * fields. That error envelope is screened in
 * {@see Authorization::getAccessToken()}
 * BEFORE this DTO is hydrated, so this DTO only ever models a *successful*
 * exchange: the token fields below are required (their absence is a real bug,
 * not a valid state). Only the grant-dependent id lists stay nullable.
 */
#[MapName(SnakeCaseMapper::class)]
class GetAccessTokenData extends Data
{
    /**
     * @param  string  $accessToken  required on success
     * @param  string  $refreshToken  required on success
     * @param  int  $expireIn  required on success (seconds)
     * @param  array<int, int>|null  $merchantIdList  present when main_account_id was used
     * @param  array<int, int>|null  $shopIdList  present when main_account_id was used
     * @param  array<int, int>|null  $supplierIdList  present when auth_type=supplier
     * @param  array<int, int>|null  $userIdList  present when auth_type=user
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expireIn,
        public ?string $requestId = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?array $merchantIdList = null,
        public ?array $shopIdList = null,
        public ?array $supplierIdList = null,
        public ?array $userIdList = null,
    ) {}
}
