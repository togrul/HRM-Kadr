<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Köhnə "components" mexanizmini təqaüdə çıxarır. Word mühərriki əmr yaradılmasını,
 * self-service məzuniyyət isə komponentlərdən ayrılandan sonra bu cədvəlləri heç nə
 * oxumur/yazmır; historical print də yalnız `template_snapshot.docx_path` ilə işləyir.
 *
 * DAĞIDICIDIR: drop-dan əvvəl `components`, `order_log_components`,
 * `order_log_component_attributes` sətirləri və `order_log_personnels.component_id`
 * dəyərləri `legacy_order_archive`-ə (JSON payload) köçürülür; down() strukturu
 * yenidən qurub məlumatı arxivdən geri yazır.
 */
return new class extends Migration
{
    private const ARCHIVE = 'legacy_order_archive';

    private const COMPONENT_ID_SOURCE = 'order_log_personnels.component_id';

    public function up(): void
    {
        if (! Schema::hasTable(self::ARCHIVE)) {
            throw new RuntimeException('legacy_order_archive table is missing; run 2026_06_20_110000 first.');
        }

        $this->archiveTable('order_log_component_attributes');
        $this->archiveTable('order_log_components');
        $this->archiveTable('components');
        $this->archiveComponentLinks();

        // Dependent tables / FKs first (they reference components).
        Schema::dropIfExists('order_log_component_attributes');
        Schema::dropIfExists('order_log_components');

        if (Schema::hasColumn('order_log_personnels', 'component_id')) {
            Schema::table('order_log_personnels', function (Blueprint $table) {
                $table->dropConstrainedForeignId('component_id');
            });
        }

        Schema::dropIfExists('components');
    }

    public function down(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_type_id')->nullable();
            $table->unsignedBigInteger('rank_id')->nullable();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->string('title')->nullable();
            $table->json('dynamic_fields')->nullable();
            $table->timestamps();
        });

        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()->after('tabel_no');
        });

        Schema::create('order_log_components', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreignId('component_id');
            $table->integer('row_number')->nullable();
            $table->timestamps();
        });

        Schema::create('order_log_component_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->foreignId('component_id');
            $table->json('attributes')->nullable();
            $table->integer('row_number')->nullable();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable(self::ARCHIVE)) {
            return;
        }

        $this->restoreTable('components');
        $this->restoreTable('order_log_components');
        $this->restoreTable('order_log_component_attributes');
        $this->restoreComponentLinks();
    }

    /**
     * Cədvəl hələ mövcuddursa, mənbə odur: əvvəlki yarımçıq cəhdin arxiv sətirləri
     * silinib yenidən yazılır ki, təkrar run dublikat yaratmasın.
     */
    private function archiveTable(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table(self::ARCHIVE)->where('source', $table)->delete();

        DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($table): void {
            DB::table(self::ARCHIVE)->insert($rows->map(fn (object $row): array => [
                'source' => $table,
                'source_key' => (string) $row->id,
                'payload' => json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'archived_at' => now(),
            ])->all());
        });
    }

    private function archiveComponentLinks(): void
    {
        if (! Schema::hasColumn('order_log_personnels', 'component_id')) {
            return;
        }

        DB::table(self::ARCHIVE)->where('source', self::COMPONENT_ID_SOURCE)->delete();

        DB::table('order_log_personnels')
            ->whereNotNull('component_id')
            ->orderBy('id')
            ->select(['id', 'component_id'])
            ->chunk(500, function ($rows): void {
                DB::table(self::ARCHIVE)->insert($rows->map(fn (object $row): array => [
                    'source' => self::COMPONENT_ID_SOURCE,
                    'source_key' => (string) $row->id,
                    'payload' => json_encode((array) $row, JSON_THROW_ON_ERROR),
                    'archived_at' => now(),
                ])->all());
            });
    }

    private function restoreTable(string $table): void
    {
        $columns = array_flip(Schema::getColumnListing($table));

        DB::table(self::ARCHIVE)->where('source', $table)->orderBy('id')->chunk(500, function ($rows) use ($table, $columns): void {
            DB::table($table)->insert($rows->map(
                fn (object $row): array => array_intersect_key(json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR), $columns)
            )->all());
        });

        DB::table(self::ARCHIVE)->where('source', $table)->delete();
    }

    private function restoreComponentLinks(): void
    {
        DB::table(self::ARCHIVE)->where('source', self::COMPONENT_ID_SOURCE)->orderBy('id')->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                $payload = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

                DB::table('order_log_personnels')
                    ->where('id', $payload['id'])
                    ->update(['component_id' => $payload['component_id']]);
            }
        });

        DB::table(self::ARCHIVE)->where('source', self::COMPONENT_ID_SOURCE)->delete();
    }
};
