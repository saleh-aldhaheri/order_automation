<?php

namespace App\Integrations\Shopee\Requests;

use App\Data\Shopee\GetTokenData;
use InvalidArgumentException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class GetToken extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;
    public bool $isPublic = true;

    public function __construct(
        private readonly string $code,
        private readonly int $partnerId,
        private readonly int|string $accountId,
        private readonly string $idType,   // 'shop_id' | 'main_account_id'
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/auth/token/get';
    }

    protected function defaultBody(): array
    {

        return [
            'code'       => $this->code,
            'partner_id' => $this->partnerId,
            $this->idType         => $this->accountId,
        ];
    }

    public function createDtoFromResponse(Response $response): GetTokenData
    {
        return GetTokenData::fromArray($response->json());
    }
}
