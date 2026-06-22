<?php

namespace App\Integrations\Shopee\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Urgency tag on a package (get_package_detail `response.package_list[].status_info_tag`).
 */
#[MapInputName(SnakeCaseMapper::class)]
class StatusInfoTagData extends Data
{
    public function __construct(
        public ?int $tagId = null,
        public ?int $timestamp = null,
    ) {}
}
