<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Köhnə təqvim ili balansları (`vacations`) silinmir: iş ili uçotuna köçürülən sətir
 * `migrated_at` ilə işarələnir ki, köçürmə təkrarlanmasın və geri qaytarıla bilsin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vacations', 'migrated_at')) {
            Schema::table('vacations', function (Blueprint $table): void {
                $table->timestamp('migrated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vacations', 'migrated_at')) {
            Schema::table('vacations', function (Blueprint $table): void {
                $table->dropColumn('migrated_at');
            });
        }
    }
};
