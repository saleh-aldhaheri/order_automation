<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Data\GetAccessTokenData;
use App\Integrations\Shopee\Data\RefreshAccessTokenData;
use App\Integrations\Shopee\Requests\Authorization\GetAccessToken;
use App\Integrations\Shopee\Requests\Authorization\RefreshAccessToken;
use App\Integrations\Shopee\Resource;

class Authorization extends Resource
{
    public function refreshAccessToken(): RefreshAccessTokenData
    {
        return $this->connector->send(new RefreshAccessToken(
            $this->connector->refreshToken,
            $this->connector->partnerId,
            $this->connector->shopId,
        ))->dtoOrFail();
    }

    public function getAccessToken(
        string $code,
        int|string $accountId,
        string $idType,
    ): GetAccessTokenData {
        return $this->connector->send(new GetAccessToken(
            $code,
            $this->connector->partnerId,
            $accountId,
            $idType
        ))->dtoOrFail();
    }
}
