<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_types', function (Blueprint $table) {
            if (! Schema::hasColumn('order_types', 'code')) {
                $table->string('code')->nullable()->after('name');
            }

            if (! Schema::hasColumn('order_types', 'handler_class')) {
                $table->string('handler_class')->nullable()->after('code');
            }

            if (! Schema::hasColumn('order_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('handler_class');
            }

            if (! Schema::hasColumn('order_types', 'meta')) {
                $table->json('meta')->nullable()->after('is_active');
            }
        });

        DB::table('order_types')
            ->select(['id', 'name', 'code'])
            ->orderBy('id')
            ->get()
            ->each(function ($type): void {
                if (trim((string) $type->code) !== '') {
                    return;
                }

                $slug = Str::slug((string) $type->name);
                $code = ($slug !== '' ? $slug : 'order-type').'-'.(int) $type->id;

                DB::table('order_types')
                    ->where('id', $type->id)
                    ->update(['code' => $code]);
            });

        Schema::table('order_types', function (Blueprint $table) {
            $table->unique('code', 'ot_code_unique');
            $table->index('is_active', 'ot_active_idx');
            $table->index('handler_class', 'ot_handler_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_types', function (Blueprint $table) {
            $table->dropUnique('ot_code_unique');
            $table->dropIndex('ot_active_idx');
            $table->dropIndex('ot_handler_idx');

            $table->dropColumn([
                'code',
                'handler_class',
                'is_active',
                'meta',
            ]);
        });
    }
};
