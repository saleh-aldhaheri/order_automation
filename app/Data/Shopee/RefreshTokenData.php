<?php

namespace App\Data\Shopee;

use RuntimeException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;


class RefreshTokenData extends Data
{
    public function __construct(

        #[Required]
        public string $refreshToken,

        #[Required]
        public string $accessToken,

        #[Required]
        public  int    $expireIn,

        public ?int $shopId = null,
        public ?string $requestId = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?int $partnerId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        if (! empty($data['error'])) {
            throw new RuntimeException(
                'Shopee refresh token request failed: ' . ($data['message'] ?? $data['error'])
            );
        }

        return new self(
            refreshToken: $data['refresh_token'],
            accessToken: $data['access_token'],
            expireIn: $data['expire_in'],
            requestId: $data['request_id'] ?? null,
            error: $data['error'] ?: null,
            message: $data['message'] ?? null,
            shopId: $data['shop_id'] ?? null,
            partnerId: $data['partner_id'] ?? null,
        );
    }
}
