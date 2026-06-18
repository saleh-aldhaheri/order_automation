<?php

namespace App\Integrations\Shopee\Resources;

use App\Data\Integrations\Shopee\GetTokenData;
use App\Data\Integrations\Shopee\RefreshTokenData;
use App\Integrations\Shopee\Requests\GetToken;
use App\Integrations\Shopee\Requests\RefreshToken;
use App\Integrations\Shopee\Resource;

class Authorization extends Resource
{
    public function refreshToken(): RefreshTokenData
    {
        return $this->connector->send(new RefreshToken(
            $this->connector->refreshToken,
            $this->connector->partnerId,
            $this->connector->accountId,
        ))->dtoOrFail();
    }

    public function getToken(
        string $code,
        int|string $accountId,
        string $idType,
    ): GetTokenData {
        return $this->connector->send(new GetToken(
            $code,
            $this->connector->partnerId,
            $accountId,
            $idType
        ))->dtoOrFail();
    }
}
