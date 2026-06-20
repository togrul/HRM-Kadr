<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_delivery_records')) {
            Schema::table('training_delivery_records', function (Blueprint $table): void {
                if (! Schema::hasColumn('training_delivery_records', 'certificate_path')) {
                    $table->string('certificate_path')->nullable()->after('result_status');
                }

                if (! Schema::hasColumn('training_delivery_records', 'certificate_name')) {
                    $table->string('certificate_name')->nullable()->after('certificate_path');
                }
            });
        }

        if (Schema::hasTable('training_sessions')) {
            Schema::table('training_sessions', function (Blueprint $table): void {
                if (! Schema::hasColumn('training_sessions', 'auto_fill_participants')) {
                    $table->boolean('auto_fill_participants')->default(true)->after('planned_budget');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('training_delivery_records')) {
            Schema::table('training_delivery_records', function (Blueprint $table): void {
                if (Schema::hasColumn('training_delivery_records', 'certificate_name')) {
                    $table->dropColumn('certificate_name');
                }

                if (Schema::hasColumn('training_delivery_records', 'certificate_path')) {
                    $table->dropColumn('certificate_path');
                }
            });
        }

        if (Schema::hasTable('training_sessions')) {
            Schema::table('training_sessions', function (Blueprint $table): void {
                if (Schema::hasColumn('training_sessions', 'auto_fill_participants')) {
                    $table->dropColumn('auto_fill_participants');
                }
            });
        }
    }
};
