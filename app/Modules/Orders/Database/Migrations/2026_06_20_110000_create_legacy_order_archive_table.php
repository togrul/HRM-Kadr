<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Köhnə (designer/Word mühərrikindən əvvəlki) əmr strukturları üçün arxiv cədvəli.
 *
 * Dağıdıcı Orders miqrasiyaları (sütun/cədvəl drop) silməzdən əvvəl hər sətri bura
 * `source` + `source_key` + tam JSON `payload` kimi köçürür; öz down()-ları məlumatı
 * buradan geri yazır. Tarix 2026_06_20_120000-dan əvvəl seçilib ki, təmiz quraşdırmada
 * komponent cədvəllərinin drop-undan əvvəl yaradılsın.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('legacy_order_archive')) {
            return;
        }

        Schema::create('legacy_order_archive', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64);
            $table->string('source_key', 191);
            $table->json('payload');
            $table->timestamp('archived_at')->nullable();

            $table->index(['source', 'source_key'], 'legacy_order_archive_source_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('legacy_order_archive')) {
            return;
        }

        // Arxivdə məlumat qalıbsa cədvəli silmək geri qaytarılmaz itki olardı.
        if (DB::table('legacy_order_archive')->exists()) {
            throw new RuntimeException(
                'legacy_order_archive is not empty: roll back the migrations that archived into it first.'
            );
        }

        Schema::drop('legacy_order_archive');
    }
};
