<?php

namespace App\Modules\Orders\Console\Commands;

use App\Modules\Orders\Application\Document\LegacyOrderTemplateNeutralizer;
use Illuminate\Console\Command;

/**
 * Standart əmr şablonlarından köhnə şirkət/şəxs adlarını çıxarır və Əmək Məcəlləsi
 * istinadlarını düzəldir (yalnız HR tərəfindən dəyişdirilməmiş şablonlarda).
 * --dry-run ilə heç nə yazmır, sadəcə nəyin dəyişəcəyini göstərir.
 */
class NeutralizeOrderWordTemplatesCommand extends Command
{
    protected $signature = 'orders:neutralize-word-templates {--dry-run : Show what would change without writing}';

    protected $description = 'Remove legacy company/person names from unedited standard order templates and fix article references';

    public function handle(LegacyOrderTemplateNeutralizer $neutralizer): int
    {
        $result = $neutralizer->run((bool) $this->option('dry-run'));

        foreach ($result as $bucket => $codes) {
            $this->line(sprintf('%-8s %d  %s', $bucket, count($codes), implode(', ', $codes)));
        }

        return self::SUCCESS;
    }
}
