<?php

namespace App\Integrations\Shopee\Requests;

use App\Data\Shopee\RefreshTokenData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class RefreshToken extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;
    public bool $isPublic = true;

    public function __construct(
        private readonly string $refreshToken,
        private readonly int $partnerId,
        private readonly int|string $accountId,
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
            'shop_id'            => $this->accountId,
        ];
    }

    public function createDtoFromResponse(Response $response): RefreshTokenData
    {
        return RefreshTokenData::fromArray($response->json());
    }
}
