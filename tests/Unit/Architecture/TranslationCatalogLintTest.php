<?php

namespace Tests\Unit\Architecture;

use App\Support\Translations\TranslationCatalogLinter;
use Tests\TestCase;

class TranslationCatalogLintTest extends TestCase
{
    public function test_module_translation_catalogs_follow_canonical_standard(): void
    {
        $findings = app(TranslationCatalogLinter::class)->lint();

        $this->assertSame(
            [],
            $findings,
            collect($findings)
                ->map(fn (array $finding) => sprintf(
                    '[%s] %s %s: %s',
                    $finding['severity'],
                    $finding['rule'],
                    $finding['file'],
                    $finding['message']
                ))
                ->implode(PHP_EOL)
        );
    }
}
