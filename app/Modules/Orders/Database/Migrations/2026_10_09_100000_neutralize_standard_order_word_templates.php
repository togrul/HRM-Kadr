<?php

use App\Modules\Orders\Application\Document\LegacyOrderTemplateNeutralizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Earlier seeds of the standard order catalogue printed one customer's company name and
 * a named accounting head ("Səbuhi Bağırov") into the template text, and some Labour Code
 * references were wrong. Templates whose stored text still matches a seeded version are
 * rewritten in place (company name → [Təşkilatın adı] / neutral wording, named person →
 * "Mühasibatlıq", articles corrected); templates edited by HR are left untouched.
 * Idempotent: a template already matching the current catalogue is skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_word_templates') || ! DB::table('order_word_templates')->exists()) {
            return;
        }

        $result = app(LegacyOrderTemplateNeutralizer::class)->run();

        Log::info('orders.word_templates.neutralized', $result);
    }

    public function down(): void
    {
        // The previous masters are archived as template versions; nothing to undo here.
    }
};
