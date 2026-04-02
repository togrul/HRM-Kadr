<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('components')) {
            return;
        }

        $hasOrderId = Schema::hasColumn('components', 'order_id');
        $hasOrderTypeId = Schema::hasColumn('components', 'order_type_id');

        if ($hasOrderId) {
            Schema::table('components', function (Blueprint $table) {
                if ($this->foreignKeyExists('components', 'components_order_id_foreign')) {
                    $table->dropForeign('components_order_id_foreign');
                }

                $table->dropColumn('order_id');
            });
        }

        if (! $hasOrderTypeId) {
            Schema::table('components', function (Blueprint $table) {
                $table->foreignIdFor(\App\Models\OrderType::class)
                    ->nullable()
                    ->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('components', function (Blueprint $table) {
            //
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
