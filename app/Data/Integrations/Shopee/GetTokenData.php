<?php

namespace App\Data\Integrations\Shopee;

use RuntimeException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class GetTokenData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(

        #[Required]
        public string $refreshToken,

        #[Required]
        public string $accessToken,

        #[Required]
        public  int    $expireIn,

        public  ?string $requestId = null,
        public  ?string $error = null,
        public  ?string $message = null,
        public  ?array  $merchantIdList = null,
        public  ?array  $shopIdList = null,
        public  ?array  $supplierIdList = null,
        public  ?array  $userIdList = null

    ) {}

    public static function fromArray(array $data): self
    {
        if (! empty($data['error'])) {
            throw new RuntimeException(
                'Shopee get token request failed: ' . ($data['message'] ?? $data['error'])
            );
        }

        return new self(
            refreshToken: $data['refresh_token'],
            accessToken: $data['access_token'],
            expireIn: $data['expire_in'],
            requestId: $data['request_id'] ?? null,
            error: $data['error'] ?? null,
            message: $data['message'] ?? null,
            merchantIdList: $data['merchant_id_list'] ?? null,
            shopIdList: $data['shop_id_list'] ?? null,
            supplierIdList: $data['supplier_id_list'] ?? null,
            userIdList: $data['user_id_list'] ?? null,
        );
    }
}
