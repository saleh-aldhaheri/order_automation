<?php

namespace App\Integrations\Shopee\Resources;

use App\Integrations\Shopee\Resource;
use App\Integrations\Shopee\Exceptions\ShopeeException;
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
    /**
     * Refresh the shop access token using the stored refresh token.
     */
    public function refreshAccessToken(): RefreshAccessTokenData
    {
        $response = $this->connector->send(new RefreshAccessToken(
            $this->connector->refreshToken,
            $this->connector->partnerId,
            $this->connector->shopId,
        ));

        //because shopee return 200 even if the refresh failed
        if (! empty($error = $response->json('error'))) {
            throw new ShopeeException(
                'Shopee refresh access token failed: ' . ($response->json('message') ?: $error)
            );
        }

        return $response->dtoOrFail();
    }

    /**
     * Exchange an authorization code for a shop access token.
     */
    public function getAccessToken(
        string $code,
        int|string $shopId,
        string $idType,
    ): GetAccessTokenData {
        $response = $this->connector->send(new GetAccessToken(
            $code,
            $this->connector->partnerId,
            $shopId,
            $idType
        ));

        //because shopee return 200 even if can't get the access token
        if (! empty($error = $response->json('error'))) {
            throw new ShopeeException(
                'Shopee get access token failed: ' . ($response->json('message') ?: $error)
            );
        }

        return $response->dtoOrFail();
    }
}
