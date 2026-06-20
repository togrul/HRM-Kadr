<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_logs', function (Blueprint $table) {
            $table->index(['order_id', 'deleted_at', 'given_date'], 'order_logs_order_deleted_date_idx');
            $table->index(['status_id', 'deleted_at', 'given_date'], 'order_logs_status_deleted_date_idx');
        });

        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->index(['order_no', 'tabel_no'], 'order_log_personnels_order_tabel_idx');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->index(['structure_id', 'deleted_at', 'tabel_no'], 'personnels_structure_deleted_tabel_idx');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropIndex('personnels_structure_deleted_tabel_idx');
        });

        Schema::table('order_log_personnels', function (Blueprint $table) {
            $table->dropIndex('order_log_personnels_order_tabel_idx');
        });

        Schema::table('order_logs', function (Blueprint $table) {
            $table->dropIndex('order_logs_status_deleted_date_idx');
            $table->dropIndex('order_logs_order_deleted_date_idx');
        });
    }
};
