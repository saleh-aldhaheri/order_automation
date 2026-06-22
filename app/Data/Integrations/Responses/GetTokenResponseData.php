<?php

namespace App\Data\Integrations\Responses;

use App\Enums\ShopsEnum;
use Spatie\LaravelData\Data;

class GetTokenResponseData extends Data
{

    public function __construct(
        public  ?string $externalShopId,
        public ShopsEnum $shopType,
        public array $authConfiguration,
        public bool $isActive = true,
        public ?array $shopIdList =  null,
    ) {}
}
