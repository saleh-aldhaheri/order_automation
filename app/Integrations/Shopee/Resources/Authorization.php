<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Resource;
use App\Integrations\Shopee\Data\{
    GetAccessTokenData,
    RefreshAccessTokenData
};
use App\Integrations\Shopee\Requests\Authorization\{
    GetAccessToken,
    RefreshAccessToken
};

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
