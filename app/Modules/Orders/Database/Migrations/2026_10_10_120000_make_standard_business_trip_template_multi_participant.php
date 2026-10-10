<?php

use App\Modules\Orders\Infrastructure\Document\StandardBusinessTripTemplateUpgrader;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The standard business-trip order becomes multi-participant (one order for a team, a
 * participants table, per-person dates, trip kind and funding source). Installed templates
 * still as seeded are replaced (the old master archived as a version, issued orders re-keyed);
 * templates edited in the designer are left alone and logged. Idempotent — see
 * StandardBusinessTripTemplateUpgrader.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates') || ! Schema::hasColumn('order_word_templates', 'multi_participant')) {
            return;
        }

        $result = app(StandardBusinessTripTemplateUpgrader::class)->run();

        if ($result['edited'] !== []) {
            Log::warning('orders.word_templates.multi_participant_upgrade_skipped_edited', ['codes' => $result['edited']]);
        }

        Log::info('orders.word_templates.multi_participant_upgrade', $result);
    }

    public function down(): void
    {
        // Multi-participant orders may already exist on the new layout; the old master stays in the version history.
    }
};
