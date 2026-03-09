<?php

namespace App\Support\Translations;

use Illuminate\Support\Facades\File;

class TranslationCatalogLinter
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function lint(): array
    {
        $findings = [];

        foreach ((array) config('modules.catalog', []) as $slug => $entry) {
            $provider = (string) ($entry['provider'] ?? '');
            $langPath = ModuleTranslation::langPathFromProvider($provider);

            if ($langPath === null) {
                continue;
            }

            $namespace = ModuleTranslation::namespaceFromSlug((string) $slug);
            $localeDirectories = collect(File::directories($langPath))
                ->mapWithKeys(fn (string $path) => [basename($path) => $path])
                ->all();

            $filesByLocale = [];
            $keysByLocale = [];

            foreach ($localeDirectories as $locale => $localePath) {
                $files = collect(File::allFiles($localePath))
                    ->filter(fn ($file) => $file->getExtension() === 'php')
                    ->mapWithKeys(function ($file) use ($localePath) {
                        $relativePath = ltrim(str_replace($localePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);

                        return [$relativePath => $file->getPathname()];
                    })
                    ->all();

                $filesByLocale[$locale] = $files;

                foreach ($files as $relativePath => $absolutePath) {
                    $catalog = include $absolutePath;
                    $relativeFile = str_replace(base_path().DIRECTORY_SEPARATOR, '', $absolutePath);

                    if (! is_array($catalog)) {
                        $findings[] = $this->finding(
                            'error',
                            'non_array_translation_file',
                            $relativeFile,
                            sprintf('Translation file for [%s] must return an array.', $namespace)
                        );

                        continue;
                    }

                    $this->lintArrayKeys($catalog, $relativeFile, $findings);
                    $keysByLocale[$locale][$relativePath] = $this->flattenKeys($catalog);
                }
            }

            $allRelativeFiles = collect($filesByLocale)
                ->flatMap(fn (array $files) => array_keys($files))
                ->unique()
                ->values()
                ->all();

            foreach ($allRelativeFiles as $relativePath) {
                foreach (array_keys($localeDirectories) as $locale) {
                    if (! isset($filesByLocale[$locale][$relativePath])) {
                        $findings[] = $this->finding(
                            'error',
                            'missing_locale_file',
                            $langPath,
                            sprintf(
                                'Missing [%s] translation file for locale [%s]: %s',
                                $namespace,
                                $locale,
                                $relativePath
                            )
                        );
                    }
                }

                $referenceLocale = array_key_first($localeDirectories);
                $referenceKeys = $referenceLocale !== null
                    ? ($keysByLocale[$referenceLocale][$relativePath] ?? [])
                    : [];

                foreach (array_keys($localeDirectories) as $locale) {
                    $candidateKeys = $keysByLocale[$locale][$relativePath] ?? [];

                    if ($referenceKeys === $candidateKeys) {
                        continue;
                    }

                    $missing = array_values(array_diff($referenceKeys, $candidateKeys));
                    $extra = array_values(array_diff($candidateKeys, $referenceKeys));

                    if ($missing !== []) {
                        $findings[] = $this->finding(
                            'error',
                            'missing_translation_keys',
                            $filesByLocale[$locale][$relativePath] ?? $langPath,
                            sprintf(
                                'Locale [%s] is missing keys from %s: %s',
                                $locale,
                                $relativePath,
                                implode(', ', $missing)
                            )
                        );
                    }

                    if ($extra !== []) {
                        $findings[] = $this->finding(
                            'error',
                            'extra_translation_keys',
                            $filesByLocale[$locale][$relativePath] ?? $langPath,
                            sprintf(
                                'Locale [%s] has extra keys in %s: %s',
                                $locale,
                                $relativePath,
                                implode(', ', $extra)
                            )
                        );
                    }
                }
            }
        }

        return $findings;
    }

    /**
     * @param array<string|int, mixed> $catalog
     * @param array<int, array<string, mixed>> $findings
     */
    private function lintArrayKeys(array $catalog, string $relativeFile, array &$findings, string $path = ''): void
    {
        $normalizedSiblings = [];

        foreach ($catalog as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $currentPath = $path === '' ? $key : $path.'.'.$key;
            $normalizedKey = ModuleTranslation::canonicalSegment($key);

            if (! ModuleTranslation::isCanonicalSegment($key)) {
                $findings[] = $this->finding(
                    'error',
                    'non_canonical_key',
                    $relativeFile,
                    sprintf('Translation key [%s] must use lowercase snake_case.', $currentPath)
                );
            }

            if (isset($normalizedSiblings[$normalizedKey]) && $normalizedSiblings[$normalizedKey] !== $key) {
                $findings[] = $this->finding(
                    'error',
                    'normalized_duplicate_key',
                    $relativeFile,
                    sprintf(
                        'Sibling keys [%s] and [%s] normalize to the same canonical key.',
                        $path === '' ? $normalizedSiblings[$normalizedKey] : $path.'.'.$normalizedSiblings[$normalizedKey],
                        $currentPath
                    )
                );
            } else {
                $normalizedSiblings[$normalizedKey] = $key;
            }

            if (is_array($value)) {
                $this->lintArrayKeys($value, $relativeFile, $findings, $currentPath);
            }
        }
    }

    /**
     * @param array<string|int, mixed> $catalog
     * @return array<int, string>
     */
    private function flattenKeys(array $catalog, string $prefix = ''): array
    {
        $keys = [];

        foreach ($catalog as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $path = $prefix === '' ? $key : $prefix.'.'.$key;

            if (is_array($value)) {
                array_push($keys, ...$this->flattenKeys($value, $path));
                continue;
            }

            $keys[] = $path;
        }

        sort($keys);

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function finding(string $severity, string $rule, string $file, string $message): array
    {
        return [
            'severity' => $severity,
            'rule' => $rule,
            'file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
            'message' => $message,
        ];
    }
}
