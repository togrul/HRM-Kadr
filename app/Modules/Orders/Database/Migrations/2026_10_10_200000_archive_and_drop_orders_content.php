<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `orders.content` — köhnə DOCX/`${content}` axınının template path / HTML izi.
 *
 * Word mühərriki sənədi `order_word_templates` + `order_logs.template_snapshot.docx_path`
 * üzərindən qurur; historical print, export, Integration feed (outbox) və finance
 * proyeksiyası bu sütunu oxumur. Son oxuyan (self-service məzuniyyət binder-inin ad
 * fallback-ı) çıxarılıb.
 *
 * DAĞIDICIDIR: drop-dan əvvəl hər `orders` sətri (soft-deleted daxil) tam JSON kimi
 * `legacy_order_archive`-ə (source = `orders.content`) köçürülür. down() sütunu
 * nullable kimi bərpa edir və dəyərləri arxivdən geri yazır.
 */
return new class extends Migration
{
    private const ARCHIVE = 'legacy_order_archive';

    private const SOURCE = 'orders.content';

    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'content')) {
            return;
        }

        if (! Schema::hasTable(self::ARCHIVE)) {
            throw new RuntimeException('legacy_order_archive table is missing; run 2026_06_20_110000 first.');
        }

        // Sütun hələ varsa mənbə odur: yarımçıq əvvəlki cəhdin arxivi təzələnir.
        DB::table(self::ARCHIVE)->where('source', self::SOURCE)->delete();

        DB::table('orders')->orderBy('id')->chunk(500, function ($rows): void {
            DB::table(self::ARCHIVE)->insert($rows->map(fn (object $row): array => [
                'source' => self::SOURCE,
                'source_key' => (string) $row->id,
                'payload' => json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'archived_at' => now(),
            ])->all());
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'content')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('content')->nullable()->after('name');
            });
        }

        if (! Schema::hasTable(self::ARCHIVE)) {
            return;
        }

        DB::table(self::ARCHIVE)->where('source', self::SOURCE)->orderBy('id')->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                $payload = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

                DB::table('orders')
                    ->where('id', $payload['id'])
                    ->update(['content' => $payload['content'] ?? null]);
            }
        });

        DB::table(self::ARCHIVE)->where('source', self::SOURCE)->delete();
    }
};
