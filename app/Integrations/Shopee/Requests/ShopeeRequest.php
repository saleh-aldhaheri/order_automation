<?php

namespace App\Integrations\Shopee\Requests;

use App\Integrations\Shopee\Exceptions\ShopeeException;
use Saloon\Http\Request;
use Saloon\Http\Response;

abstract class ShopeeRequest extends Request
{
    final public function createDtoFromResponse(Response $response): mixed
    {
        try {
            return $this->toDto($response);
        } catch (\Throwable $e) {
            throw new ShopeeException($e->getMessage());
        }
    }

    abstract protected function toDto(Response $response): mixed;
}
