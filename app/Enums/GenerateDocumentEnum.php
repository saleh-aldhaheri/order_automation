<?php

namespace App\Enums;

use RuntimeException;

enum GenerateDocumentEnum: string
{
    case START_PROCESS = 'start process';
    case DOCUMENT_TYPE = 'document type';
    case CREATE_DOCUMENT = 'create document';

    /**
     * Steps a given shop type supports, in execution order.
     *
     * @return array<int, self>
     */
    public static function for(ShopsEnum $shopType): array
    {
        return match ($shopType) {
            ShopsEnum::SHOPEE => [self::START_PROCESS, self::DOCUMENT_TYPE, self::CREATE_DOCUMENT],
            default => throw new RuntimeException("Arrangement steps are not defined for {$shopType->value}"),
        };
    }

    public static function isValidStep(self $step, ShopsEnum $shopType): bool
    {
        return in_array($step, self::for($shopType), true);
    }

    public static function ensureSupported(self $step, ShopsEnum $shopType): void
    {
        if (! self::isValidStep($step, $shopType)) {
            throw new RuntimeException("Step '{$step->value}' is not supported by {$shopType->value}");
        }
    }
}
