<?php

namespace App\Support\Permissions;

/**
 * Human-readable permission descriptions, kept in lang/{az,en}/permission_descriptions.php.
 *
 * Two readers with different needs: migrations store a description in
 * `permissions.description` (data — always Azerbaijani, `describe()`),
 * and the role/permission screen shows one to whoever is looking (`label()`).
 */
class PermissionDescriptionCatalog
{
    /**
     * Stored descriptions are Azerbaijani, as they always were. Not config('app.locale'):
     * App::setLocale() rewrites that per request, so it cannot anchor stored data.
     */
    public const STORED_LOCALE = 'az';

    /**
     * @return array<string,string>
     */
    public static function all(?string $locale = null): array
    {
        $items = trans('permission_descriptions.items', [], $locale ?? app()->getLocale());

        return is_array($items) ? $items : [];
    }

    /**
     * The description to store — always in STORED_LOCALE, whoever runs the migration.
     */
    public static function describe(string $permission): string
    {
        return self::in($permission, self::STORED_LOCALE);
    }

    /**
     * The description to show, in the current user's language.
     */
    public static function label(string $permission): string
    {
        return self::in($permission, app()->getLocale());
    }

    private static function in(string $permission, string $locale): string
    {
        return self::all($locale)[$permission] ?? (string) trans('permission_descriptions.fallback', [], $locale);
    }
}
