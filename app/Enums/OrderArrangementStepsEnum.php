<?php

namespace App\Enums;

use App\Services\PackageService;
use RuntimeException;

/**
 * Catalogue of every order-arrangement step the system supports across all
 * providers. Drives orchestration and validation — which steps a given shop can
 * run, and in what order. The actual work is done by typed methods on
 * {@see PackageService} that wrap the provider's typed calls.
 */
enum OrderArrangementStepsEnum: string
{
    case START_PROCESS = 'start process';
    case PICKUP = 'pickup option';
    case SHIP = 'ship';

    /**
     * Steps a given shop type supports, in execution order.
     *
     * @return array<int, self>
     */
    public static function for(ShopsEnum $shopType): array
    {
        return match ($shopType) {
            ShopsEnum::SHOPEE => [self::START_PROCESS, self::PICKUP, self::SHIP],
            default => throw new RuntimeException("Arrangement steps are not defined for {$shopType->value}"),
        };
    }

    public static function isValidStep(self $step, ShopsEnum $shopType): bool
    {
        return in_array($step, self::for($shopType), true);
    }

    /**
     * Guard: throw when the step isn't supported by the shop type.
     */
    public static function ensureSupported(self $step, ShopsEnum $shopType): void
    {
        if (! self::isValidStep($step, $shopType)) {
            throw new RuntimeException("Step '{$step->value}' is not supported by {$shopType->value}");
        }
    }
}
