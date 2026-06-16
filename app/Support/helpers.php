<?php

use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting_media_url')) {
    /**
     * Resolve a stored branding path (public disk) or legacy absolute URL for img/link src.
     */
    function setting_media_url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
}

if (! function_exists('setting')) {
    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingService::class);

        if ($key === null) {
            return $service->getSettings();
        }

        return $service->get($key, $default);
    }
}
