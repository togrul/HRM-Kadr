<?php

namespace App\Modules\Orders\Console\Commands;

use App\Services\Orders\Document\OrderTemplatePresets;
use App\Services\Orders\Document\OrderTemplateRepository;
use Illuminate\Console\Command;

/**
 * Seeds the built-in order presets into the order_block_templates table so the
 * designer/composer load real, editable templates from the database. Idempotent —
 * re-running updates the rows in place (updateOrCreate by code).
 */
class SeedOrderBlockTemplatesCommand extends Command
{
    protected $signature = 'orders:templates:seed-presets {--force : Overwrite templates that already exist}';

    protected $description = 'Seed the built-in order presets into order_block_templates';

    public function handle(OrderTemplatePresets $presets, OrderTemplateRepository $repository): int
    {
        $seeded = 0;
        foreach ($presets->available() as $code => $label) {
            if (! $this->option('force') && $repository->exists($code)) {
                $this->line("skip (exists): {$code}");

                continue;
            }

            $repository->save($code, $label, $presets->blocks($code));
            $this->info("seeded: {$code} — {$label}");
            $seeded++;
        }

        $this->info("Done. {$seeded} template(s) seeded.");

        return self::SUCCESS;
    }
}
