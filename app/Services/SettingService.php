<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SettingService
{
    /** @var array<string, array<string, mixed>> */
    private array $defaultSettings;

    public function __construct()
    {
        $this->defaultSettings = config('settings.fields');
    }

    public function getSettings(): array
    {
        if (! Schema::hasTable('settings')) {
            return $this->defaultsOnly();
        }

        $model = Setting::query()->first();
        $dbSettings = is_array($model?->settings) ? $model->settings : [];

        return collect($this->defaultSettings)->mapWithKeys(function (array $meta, string $key) use ($dbSettings) {
            return [$key => $this->resolveValue($key, $meta, $dbSettings)];
        })->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getSettings();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function setSettings(array $settings): array
    {
        $normalized = $this->normalizeIncoming($settings);
        $this->validate($normalized);

        $model = Setting::query()->firstOrCreate(
            [],
            ['settings' => []],
        );
        $current = is_array($model->settings) ? $model->settings : [];

        $model->update([
            'settings' => array_merge($current, $normalized),
        ]);

        return $this->getSettings();
    }

    private function resolveValue(string $key, array $meta, array $dbSettings): mixed
    {
        $default = $meta['default'] ?? null;

        if (! array_key_exists($key, $dbSettings)) {
            return $default;
        }

        $value = $dbSettings[$key];

        if ($value === null && ($meta['nullable'] ?? false)) {
            return null;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    private function defaultsOnly(): array
    {
        return collect($this->defaultSettings)->mapWithKeys(function (array $meta, string $key) {
            return [$key => $meta['default'] ?? null];
        })->all();
    }

    private function normalizeIncoming(array $settings): array
    {
        foreach ($settings as $key => $value) {
            $meta = $this->defaultSettings[$key] ?? null;
            if (! $meta) {
                continue;
            }
            if (($meta['type'] ?? null) === 'string' && ($meta['nullable'] ?? false) && $value === '') {
                $settings[$key] = null;
            }
            if (($meta['type'] ?? null) === 'boolean' && ! is_bool($value)) {
                $settings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $settings;
    }

    private function validate(array $settings): void
    {
        $defaultKeys = array_keys($this->defaultSettings);
        $invalidKeys = array_diff(array_keys($settings), $defaultKeys);

        if ($invalidKeys !== []) {
            throw ValidationException::withMessages([
                'settings' => [
                    __('Invalid setting keys: :keys', ['keys' => implode(', ', $invalidKeys)]),
                ],
            ]);
        }

        foreach ($settings as $key => $value) {
            $field = $this->defaultSettings[$key];

            switch ($field['type']) {
                case 'boolean':
                    if (! is_bool($value)) {
                        throw ValidationException::withMessages([
                            'settings' => [__('The :key setting must be a boolean.', ['key' => $key])],
                        ]);
                    }
                    break;
                case 'enum':
                    if (! in_array($value, $field['options'], true)) {
                        throw ValidationException::withMessages([
                            'settings' => [
                                __(
                                    'The :key setting must be one of: :options.',
                                    [
                                        'key' => $key,
                                        'options' => implode(', ', $field['options']),
                                    ]
                                ),
                            ],
                        ]);
                    }
                    break;
                case 'string':
                    $nullable = (bool) ($field['nullable'] ?? false);
                    if (! is_string($value) && ! ($value === null && $nullable)) {
                        throw ValidationException::withMessages([
                            'settings' => [
                                $nullable
                                    ? __('The :key setting must be a string or empty.', ['key' => $key])
                                    : __('The :key setting must be a string.', ['key' => $key]),
                            ],
                        ]);
                    }
                    break;
                default:
                    throw ValidationException::withMessages([
                        'settings' => [__('Unknown type for setting :key.', ['key' => $key])],
                    ]);
            }
        }
    }
}
