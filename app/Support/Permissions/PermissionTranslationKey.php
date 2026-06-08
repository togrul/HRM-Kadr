<?php

namespace App\Support\Permissions;

use App\Support\Translations\ModuleTranslation;

class PermissionTranslationKey
{
    public static function methodKeyFromPermission(string $permission): ?string
    {
        if (str_contains($permission, '.')) {
            [, $method] = array_pad(explode('.', $permission, 2), 2, null);

            if (! is_string($method) || $method === '') {
                return null;
            }

            $canonical = ModuleTranslation::canonicalSegment($method);

            return $canonical !== '' ? $canonical : null;
        }

        [$method] = array_pad(explode('-', $permission, 2), 2, null);

        if (! is_string($method) || $method === '') {
            return null;
        }

        $canonical = ModuleTranslation::canonicalSegment($method);

        return $canonical !== '' ? $canonical : null;
    }

    public static function groupKeyFromPermission(string $permission): ?string
    {
        if (str_contains($permission, '.')) {
            [$group] = array_pad(explode('.', $permission, 2), 2, null);

            if (! is_string($group) || $group === '') {
                return null;
            }

            $canonical = ModuleTranslation::canonicalSegment($group);

            return $canonical !== '' ? $canonical : null;
        }

        [, $group] = array_pad(explode('-', $permission, 2), 2, null);

        if (! is_string($group) || $group === '') {
            return null;
        }

        $canonical = ModuleTranslation::canonicalSegment($group);

        return $canonical !== '' ? $canonical : null;
    }
}
