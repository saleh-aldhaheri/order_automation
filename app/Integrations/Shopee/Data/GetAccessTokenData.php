<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shopee `auth/token/get` (Get Access Token) response — a faithful mirror of
 * Shopee's payload. Vendor language only: this DTO carries no application
 * concepts. Translation into the app's GetTokenResponseData happens in
 * {@see \App\Services\Integrations\ShopeeService}.
 *
 * On error Shopee still returns 200 with a non-empty `error` and empty token
 * fields, so every property is nullable.
 */
#[MapName(SnakeCaseMapper::class)]
class GetAccessTokenData extends Data
{
    /**
     * @param  array<int, int>|null  $merchantIdList   present when main_account_id was used
     * @param  array<int, int>|null  $shopIdList       present when main_account_id was used
     * @param  array<int, int>|null  $supplierIdList   present when auth_type=supplier
     * @param  array<int, int>|null  $userIdList       present when auth_type=user
     */
    public function __construct(
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?int $expireIn = null,
        public ?string $requestId = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?array $merchantIdList = null,
        public ?array $shopIdList = null,
        public ?array $supplierIdList = null,
        public ?array $userIdList = null,
    ) {}
}
