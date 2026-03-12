<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_sessions')) {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('training_sessions', 'training_plan_item_id')) {
                $table->foreignId('training_plan_item_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('training_plan_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('training_sessions') || ! Schema::hasColumn('training_sessions', 'training_plan_item_id')) {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('training_plan_item_id');
        });
    }
};
