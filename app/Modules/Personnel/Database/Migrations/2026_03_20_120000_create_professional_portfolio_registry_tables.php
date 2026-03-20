<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->createSqliteTables();

            return;
        }

        $this->createOrUpdateProfessionalEventRegistries();
        $this->createOrUpdateProfessionalMediaOutletRegistries();
        $this->createOrUpdateProfessionalProjectRegistries();
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_project_registries');
        Schema::dropIfExists('professional_media_outlet_registries');
        Schema::dropIfExists('professional_event_registries');
    }

    private function createOrUpdateProfessionalEventRegistries(): void
    {
        if (! Schema::hasTable('professional_event_registries')) {
            Schema::create('professional_event_registries', function (Blueprint $table) {
                $table->id();
                $table->string('registry_key');
                $table->string('event_type', 50)->nullable();
                $table->string('title');
                $table->string('organizer_name')->nullable();
                $table->integer('country_id')->nullable();
                $table->date('first_seen_at')->nullable();
                $table->date('last_seen_at')->nullable();
                $table->unsignedInteger('records_count')->default(0);
                $table->unsignedBigInteger('last_source_record_id')->nullable();
                $table->timestamps();
            });
        }

        $this->normalizeEventCountryIdColumn();
        $this->ensureUniqueIndex('professional_event_registries', 'professional_event_registries_registry_key_unique', 'registry_key');
        $this->ensureForeignKey(
            'professional_event_registries',
            'country_id',
            'pe_reg_country_fk',
            fn (Blueprint $table) => $table->foreign('country_id', 'pe_reg_country_fk')->references('id')->on('countries')->nullOnDelete()
        );
        $this->ensureForeignKey(
            'professional_event_registries',
            'last_source_record_id',
            'pe_reg_source_fk',
            fn (Blueprint $table) => $table->foreign('last_source_record_id', 'pe_reg_source_fk')->references('id')->on('personnel_event_records')->nullOnDelete()
        );
    }

    private function createOrUpdateProfessionalMediaOutletRegistries(): void
    {
        if (! Schema::hasTable('professional_media_outlet_registries')) {
            Schema::create('professional_media_outlet_registries', function (Blueprint $table) {
                $table->id();
                $table->string('registry_key');
                $table->string('publisher_name');
                $table->string('publisher_type', 50)->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedInteger('mentions_count')->default(0);
                $table->unsignedBigInteger('last_source_record_id')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureUniqueIndex('professional_media_outlet_registries', 'professional_media_outlet_registries_registry_key_unique', 'registry_key');
        $this->ensureForeignKey(
            'professional_media_outlet_registries',
            'last_source_record_id',
            'pmo_reg_source_fk',
            fn (Blueprint $table) => $table->foreign('last_source_record_id', 'pmo_reg_source_fk')->references('id')->on('personnel_media_mentions')->nullOnDelete()
        );
    }

    private function createOrUpdateProfessionalProjectRegistries(): void
    {
        if (! Schema::hasTable('professional_project_registries')) {
            Schema::create('professional_project_registries', function (Blueprint $table) {
                $table->id();
                $table->string('registry_key');
                $table->string('project_name');
                $table->string('project_code')->nullable();
                $table->string('project_type', 50)->nullable();
                $table->unsignedBigInteger('sponsor_unit_id')->nullable();
                $table->date('first_seen_at')->nullable();
                $table->date('last_seen_at')->nullable();
                $table->unsignedInteger('records_count')->default(0);
                $table->unsignedBigInteger('last_source_record_id')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureUniqueIndex('professional_project_registries', 'professional_project_registries_registry_key_unique', 'registry_key');
        $this->ensureForeignKey(
            'professional_project_registries',
            'sponsor_unit_id',
            'pp_reg_sponsor_fk',
            fn (Blueprint $table) => $table->foreign('sponsor_unit_id', 'pp_reg_sponsor_fk')->references('id')->on('structures')->nullOnDelete()
        );
        $this->ensureForeignKey(
            'professional_project_registries',
            'last_source_record_id',
            'pp_reg_source_fk',
            fn (Blueprint $table) => $table->foreign('last_source_record_id', 'pp_reg_source_fk')->references('id')->on('personnel_project_records')->nullOnDelete()
        );
    }

    private function ensureUniqueIndex(string $table, string $indexName, string $column): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
            $blueprint->unique($column, $indexName);
        });
    }

    private function ensureForeignKey(string $table, string $column, string $constraintName, callable $definition): void
    {
        if ($this->foreignKeyExists($table, $column) || $this->namedForeignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($definition) {
            $definition($blueprint);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.key_column_usage')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }

    private function namedForeignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    private function normalizeEventCountryIdColumn(): void
    {
        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'professional_event_registries')
            ->where('column_name', 'country_id')
            ->value('column_type');

        if ($columnType === 'int' || $columnType === 'int(11)') {
            return;
        }

        DB::statement('ALTER TABLE professional_event_registries MODIFY country_id INT NULL');
    }

    private function createSqliteTables(): void
    {
        Schema::create('professional_event_registries', function (Blueprint $table) {
            $table->id();
            $table->string('registry_key')->unique();
            $table->string('event_type', 50)->nullable();
            $table->string('title');
            $table->string('organizer_name')->nullable();
            $table->integer('country_id')->nullable();
            $table->date('first_seen_at')->nullable();
            $table->date('last_seen_at')->nullable();
            $table->unsignedInteger('records_count')->default(0);
            $table->foreignId('last_source_record_id')->nullable()->constrained('personnel_event_records')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('professional_media_outlet_registries', function (Blueprint $table) {
            $table->id();
            $table->string('registry_key')->unique();
            $table->string('publisher_name');
            $table->string('publisher_type', 50)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('mentions_count')->default(0);
            $table->foreignId('last_source_record_id')->nullable()->constrained('personnel_media_mentions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('professional_project_registries', function (Blueprint $table) {
            $table->id();
            $table->string('registry_key')->unique();
            $table->string('project_name');
            $table->string('project_code')->nullable();
            $table->string('project_type', 50)->nullable();
            $table->foreignId('sponsor_unit_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->date('first_seen_at')->nullable();
            $table->date('last_seen_at')->nullable();
            $table->unsignedInteger('records_count')->default(0);
            $table->foreignId('last_source_record_id')->nullable()->constrained('personnel_project_records')->nullOnDelete();
            $table->timestamps();
        });
    }
};
