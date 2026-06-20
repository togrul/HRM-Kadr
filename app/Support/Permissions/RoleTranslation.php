<?php

namespace App\Support\Permissions;

use Illuminate\Support\Str;

class RoleTranslation
{
    public static function label(string $roleName): string
    {
        $key = 'services::roles.names.'.self::key($roleName);
        $translated = __($key);

        return $translated !== $key
            ? $translated
            : Str::headline($roleName);
    }

    public static function key(string $roleName): string
    {
        return Str::of($roleName)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}
