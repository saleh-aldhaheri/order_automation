<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PermissionLabel
{
    /**
     * Permissions use "resource:action" (e.g. roles:create, roles:view). This returns a stable group key
     * from the resource (left segment): checkboxes show under "Roles" with create, view, edit, etc.
     */
    public static function resourceGroupKey(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '__general__';
        }

        if (str_contains($name, ':')) {
            [$resource] = array_pad(explode(':', $name, 2), 2, '');
            $resource = trim($resource);
            if ($resource !== '') {
                $h = self::humanize($resource);

                return $h === '' ? '__general__' : Str::lower($h);
            }
        }

        return '__general__';
    }

    public static function resourceGroupTitle(string $groupKey): string
    {
        if ($groupKey === '__general__') {
            return __('General');
        }

        return Str::title($groupKey);
    }

    public static function for(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if (str_contains($name, ':')) {
            [$resource, $action] = array_pad(explode(':', $name, 2), 2, '');
            $verb = self::actionLabel($action);
            $noun = self::resourceLabel($resource);
            if ($verb !== '' && $noun !== '') {
                return trim("{$verb} {$noun}");
            }
            if ($verb !== '') {
                return $verb;
            }
            if ($noun !== '') {
                return self::titleLabel($resource);
            }
        }

        return self::titleLabel(str_replace(':', ' ', $name));
    }

    private static function humanize(string $segment): string
    {
        $segment = trim(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', trim($segment))));

        return $segment;
    }

    private static function actionLabel(string $segment): string
    {
        $h = self::humanize($segment);

        return $h === '' ? '' : Str::title(Str::lower($h));
    }

    private static function resourceLabel(string $segment): string
    {
        $h = self::humanize($segment);

        return $h === '' ? '' : Str::lower($h);
    }

    private static function titleLabel(string $segment): string
    {
        $h = self::humanize($segment);

        return $h === '' ? '' : Str::title(Str::lower($h));
    }
}
