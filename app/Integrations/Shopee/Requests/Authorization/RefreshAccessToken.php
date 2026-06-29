<?php

namespace App\Integrations\Shopee\Requests\Authorization;

use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Requests\ShopeeRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class RefreshAccessToken extends ShopeeRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;
    public bool $isPublic = true;

    public function __construct(
        private readonly string $refreshToken,
        private readonly int $partnerId,
        private readonly int|string $shopId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/auth/access_token/get';
    }

    protected function defaultBody(): array
    {
        return [
            'refresh_token' => $this->refreshToken,
            'partner_id'    => $this->partnerId,
            'shop_id'       => $this->shopId,
        ];
    }

    public function toDto(Response $response): RefreshAccessTokenData
    {
        return RefreshAccessTokenData::from($response->json());
    }
}
