<?php

namespace App\Support\Translations;

use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

class ModuleTranslation
{
    public static function canonicalSegment(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->replaceMatches('/_+/', '_')
            ->toString();
    }

    public static function namespaceFromSlug(string $slug): string
    {
        return static::canonicalSegment($slug);
    }

    public static function isCanonicalSegment(string $value): bool
    {
        return preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $value) === 1;
    }

    public static function isCanonicalNamespacedKey(string $value): bool
    {
        return preg_match(
            '/^[a-z0-9]+(?:_[a-z0-9]+)*::[a-z0-9]+(?:_[a-z0-9]+)*(?:\.[a-z0-9]+(?:_[a-z0-9]+)*)+$/',
            trim($value)
        ) === 1;
    }

    public static function resolveStoredText(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (! static::isCanonicalNamespacedKey($trimmed)) {
            return $trimmed;
        }

        $translated = __($trimmed);

        return is_string($translated) && $translated !== '' && $translated !== $trimmed
            ? $translated
            : $trimmed;
    }

    public static function humanize(string $value): string
    {
        return Str::of(static::canonicalSegment($value))
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public static function langPathFromProvider(string $provider): ?string
    {
        try {
            $providerFile = (new ReflectionClass($provider))->getFileName();
        } catch (Throwable) {
            return null;
        }

        if (! is_string($providerFile) || $providerFile === '') {
            return null;
        }

        $path = dirname($providerFile).'/../Resources/lang';

        return is_dir($path) ? $path : null;
    }
}
