<?php

namespace App\Data\Integrations\Shopee;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ImageInfoData extends Data
{
    public function __construct(
        public ?string $imageUrl = null,
    ) {}
}
