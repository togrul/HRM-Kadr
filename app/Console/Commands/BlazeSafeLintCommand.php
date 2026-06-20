<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BlazeSafeLintCommand extends Command
{
    protected $signature = 'views:blaze-safe-lint
        {--json : Print findings as JSON}
        {--strict : Fail when warnings exist too}';

    protected $description = 'Lint Blade views for Blaze-unsafe patterns (kebab-case @props, short @php(), forbidden icon @include).';

    public function handle(): int
    {
        $findings = [];

        $bladeFiles = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->values();
        $moduleBladeFiles = collect(File::allFiles(app_path('Modules')))
            ->filter(function ($file) {
                $path = str_replace('\\', '/', $file->getPathname());
                return str_contains($path, '/Resources/views/')
                    && str_ends_with($file->getFilename(), '.blade.php');
            })
            ->values();
        $bladeFiles = $bladeFiles->merge($moduleBladeFiles)->values();

        foreach ($bladeFiles as $file) {
            $absolutePath = $file->getPathname();
            $relativePath = str_replace(base_path().'/', '', $absolutePath);
            $lines = preg_split('/\R/', (string) File::get($absolutePath)) ?: [];

            $this->scanPropsForKebabCase($lines, $relativePath, $findings);
            $this->scanShortPhpDirective($lines, $relativePath, $findings);
            $this->scanForbiddenIconIncludes($lines, $relativePath, $findings);
        }

        $errors = collect($findings)->where('severity', 'error')->count();
        $warnings = collect($findings)->where('severity', 'warning')->count();

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => [
                    'files_scanned' => $bladeFiles->count(),
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'ok' => $errors === 0 && (! (bool) $this->option('strict') || $warnings === 0),
                ],
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            if (empty($findings)) {
                $this->info('No Blaze-safe issues found.');
            } else {
                $this->table(
                    ['severity', 'rule', 'file', 'line', 'message'],
                    collect($findings)->map(fn (array $finding) => [
                        $finding['severity'],
                        $finding['rule'],
                        $finding['file'],
                        (string) $finding['line'],
                        $finding['message'],
                    ])->all()
                );
            }

            $this->newLine();
            $this->table(
                ['metric', 'value'],
                [
                    ['files_scanned', (string) $bladeFiles->count()],
                    ['errors', (string) $errors],
                    ['warnings', (string) $warnings],
                    ['strict_mode', (bool) $this->option('strict') ? 'on' : 'off'],
                ]
            );
        }

        if ($errors > 0) {
            return self::FAILURE;
        }

        if ((bool) $this->option('strict') && $warnings > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, string> $lines
     * @param array<int, array<string, mixed>> $findings
     */
    private function scanPropsForKebabCase(array $lines, string $relativePath, array &$findings): void
    {
        $inProps = false;

        foreach ($lines as $index => $line) {
            if (! $inProps && str_contains($line, '@props([')) {
                $inProps = true;
            }

            if ($inProps) {
                if (preg_match('/^\s*[\'"]([A-Za-z_][A-Za-z0-9_-]*)[\'"]\s*(=>|,)/', $line, $matches)) {
                    $propKey = (string) $matches[1];
                    if (str_contains($propKey, '-')) {
                        $findings[] = [
                            'severity' => 'error',
                            'rule' => 'kebab_case_props_key',
                            'file' => $relativePath,
                            'line' => $index + 1,
                            'message' => sprintf(
                                'Kebab-case @props key "%s" is Blaze-unsafe. Use camelCase prop keys.',
                                $propKey
                            ),
                        ];
                    }
                }

                if (str_contains($line, '])')) {
                    $inProps = false;
                }
            }
        }
    }

    /**
     * @param array<int, string> $lines
     * @param array<int, array<string, mixed>> $findings
     */
    private function scanShortPhpDirective(array $lines, string $relativePath, array &$findings): void
    {
        foreach ($lines as $index => $line) {
            if (preg_match('/@php\s*\(.*\)/', $line)) {
                $findings[] = [
                    'severity' => 'warning',
                    'rule' => 'short_php_directive',
                    'file' => $relativePath,
                    'line' => $index + 1,
                    'message' => 'Avoid @php(...) shorthand in Blaze-compiled views. Prefer @php ... @endphp block.',
                ];
            }
        }
    }

    /**
     * @param array<int, string> $lines
     * @param array<int, array<string, mixed>> $findings
     */
    private function scanForbiddenIconIncludes(array $lines, string $relativePath, array &$findings): void
    {
        foreach ($lines as $index => $line) {
            if (preg_match('/@include\s*\(\s*[\'\"]components\.icons\./', $line)) {
                $findings[] = [
                    'severity' => 'error',
                    'rule' => 'forbidden_icon_include',
                    'file' => $relativePath,
                    'line' => $index + 1,
                    'message' => "Do not use @include('components.icons.*'). Use <x-icons.* /> components.",
                ];
            }
        }
    }
}
