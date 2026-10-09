<?php

use App\Modules\Orders\Infrastructure\Document\StandardOrderEffectUpgrader;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Military muster, disciplinary sanction, substitution and salary change used to be
 * document-only; they now run an HR effect on approval. Templates still as seeded get the
 * effect and the variable roles; templates edited in the designer are left alone and
 * logged. Idempotent — see StandardOrderEffectUpgrader.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates')) {
            return;
        }

        $result = app(StandardOrderEffectUpgrader::class)->run();

        if ($result['edited'] !== []) {
            Log::warning('orders.word_templates.effect_upgrade_skipped_edited', ['codes' => $result['edited']]);
        }

        Log::info('orders.word_templates.effect_upgrade', $result);
    }

    public function down(): void
    {
        // Approved orders may already carry the effect's records; switching back would strand them.
    }
};
