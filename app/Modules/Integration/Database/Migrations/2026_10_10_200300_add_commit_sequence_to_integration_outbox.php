<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feed cursor = commit sırası, id yox.
 *
 * Auto-increment id yazılma anında verilir, hadisə isə uzun tranzaksiyanın sonunda görünür:
 * MySQL/PostgreSQL-də kiçik id böyükdən sonra commit oluna bilər və kursoru artıq onu
 * keçmiş oxucu həmin hadisəni heç vaxt görməz. `sequence` commit-dən sonra, tək sətirli
 * sayğac kilid altında verilir (OutboxSequencer), ona görə görünən ardıcıllıqda boşluq
 * yaranmır. Mövcud sətirlərdə sequence = id: köhnə kursorlar (son id) olduğu kimi işləyir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_outbox')) {
            return;
        }

        if (! Schema::hasColumn('integration_outbox', 'sequence')) {
            Schema::table('integration_outbox', function (Blueprint $table): void {
                $table->unsignedBigInteger('sequence')->nullable()->after('id');
            });

            DB::table('integration_outbox')->update(['sequence' => DB::raw('id')]);

            Schema::table('integration_outbox', function (Blueprint $table): void {
                $table->unique('sequence', 'integration_outbox_sequence_unique');
                $table->index(['topic', 'sequence'], 'integration_outbox_feed_sequence_idx');
            });
        }

        if (! Schema::hasTable('integration_outbox_sequence')) {
            Schema::create('integration_outbox_sequence', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedBigInteger('last_value')->default(0);
            });
        }

        DB::table('integration_outbox_sequence')->insertOrIgnore([
            'id' => 1,
            'last_value' => (int) DB::table('integration_outbox')->max('id'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox_sequence');

        if (Schema::hasColumn('integration_outbox', 'sequence')) {
            Schema::table('integration_outbox', function (Blueprint $table): void {
                $table->dropIndex('integration_outbox_feed_sequence_idx');
                $table->dropUnique('integration_outbox_sequence_unique');
            });

            Schema::table('integration_outbox', function (Blueprint $table): void {
                $table->dropColumn('sequence');
            });
        }
    }
};
