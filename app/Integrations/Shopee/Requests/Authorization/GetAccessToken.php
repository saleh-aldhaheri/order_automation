<?php

namespace App\Integrations\Shopee\Requests\Authorization;

use App\Integrations\Shopee\Data\GetAccessTokenData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class GetAccessToken extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;
    public bool $isPublic = true;

    public function __construct(
        private readonly string $code,
        private readonly int $partnerId,
        private readonly int|string $shopId,
        private readonly string $idType,   // 'shop_id' | 'main_account_id'
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/auth/token/get';
    }

    protected function defaultBody(): array
    {
        return [
            'code'        => $this->code,
            'partner_id'  => $this->partnerId,
            $this->idType => $this->shopId,
        ];
    }

    public function createDtoFromResponse(Response $response): GetAccessTokenData
    {
        return GetAccessTokenData::from($response->json());
    }
}
