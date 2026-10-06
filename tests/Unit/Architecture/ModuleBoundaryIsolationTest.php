<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Enforces the project's core rule: a module must not reach into another module's
 * INTERNAL classes. The only sanctioned cross-module surface is a target module's
 * `Contracts\` namespace (interfaces bound in its service provider).
 *
 * Any `use App\Modules\<Other>\...` that is not a `Contracts\` import is a boundary
 * violation. Existing violations are pinned in ALLOWED_DEBT below so this gate is green
 * today while blocking every NEW one — the list is debt to burn down by introducing
 * contracts, never to grow.
 */
class ModuleBoundaryIsolationTest extends TestCase
{
    /**
     * Known, pre-existing cross-module internal couplings (consumer module => imported FQCN).
     * Shrink this list as contracts are introduced; do not add to it.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_DEBT = [
        'Candidates' => [
            'App\Modules\EmployeeLifecycle\Application\Services\LifecyclePlanTemplateService',
        ],
        'Personnel' => [
            'App\Modules\Notifications\Support\DispatchesNotificationRefresh',
            'App\Modules\Notifications\Support\NotificationCountCache',
        ],
    ];

    public function test_modules_do_not_import_other_modules_internal_classes(): void
    {
        $violations = [];

        foreach (File::allFiles(app_path('Modules')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $consumerModule = $this->moduleOf($relative);
            if ($consumerModule === null) {
                continue;
            }

            foreach ($this->moduleImports($file->getContents()) as $import) {
                $targetModule = $this->moduleOf('app/Modules/'.str_replace('\\', '/', str_replace('App\\Modules\\', '', $import)));

                if ($targetModule === null || $targetModule === $consumerModule) {
                    continue; // same module — always allowed
                }

                if (str_contains($import, '\\Contracts\\')) {
                    continue; // sanctioned boundary surface
                }

                if (in_array($import, self::ALLOWED_DEBT[$consumerModule] ?? [], true)) {
                    continue; // pinned existing debt
                }

                $violations[] = $relative.' imports '.$import;
            }
        }

        $this->assertSame(
            [],
            $violations,
            "New cross-module internal import(s) detected. Depend on the target module's Contracts\\ "
            ."interface instead, or (only for legacy code) pin it in ALLOWED_DEBT:\n".implode("\n", $violations)
        );
    }

    /** Extract the module name from a repo-relative path under app/Modules/. */
    private function moduleOf(string $relativePath): ?string
    {
        if (preg_match('#app/Modules/([^/\\\\]+)/#', $relativePath, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * Every `use App\Modules\...;` import in a file (class, function, or const).
     *
     * @return list<string>
     */
    private function moduleImports(string $contents): array
    {
        if (preg_match_all('/^use\s+(?:function\s+|const\s+)?(App\\\\Modules\\\\[^;\s]+)\s*;/m', $contents, $matches) === false) {
            return [];
        }

        // Strip any "as Alias" suffix the regex may have captured boundary-wise.
        return array_map(static fn (string $fqcn): string => trim($fqcn), $matches[1]);
    }
}
