<?php

namespace App\Console\Commands;

use App\Support\Translations\TranslationCatalogLinter;
use Illuminate\Console\Command;

class TranslationLintCommand extends Command
{
    protected $signature = 'translations:lint
        {--json : Print findings as JSON}';

    protected $description = 'Lint module translation catalogs for canonical namespaced PHP translation standards.';

    public function handle(TranslationCatalogLinter $linter): int
    {
        $findings = $linter->lint();

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => [
                    'errors' => count($findings),
                    'ok' => $findings === [],
                ],
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $findings === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($findings === []) {
            $this->info('No translation catalog issues found.');

            return self::SUCCESS;
        }

        $this->table(
            ['severity', 'rule', 'file', 'message'],
            collect($findings)->map(fn (array $finding) => [
                $finding['severity'],
                $finding['rule'],
                $finding['file'],
                $finding['message'],
            ])->all()
        );

        return self::FAILURE;
    }
}
