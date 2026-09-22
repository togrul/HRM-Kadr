<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A monetary award (pul mükafatı) records how much was paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personnel_awards', 'amount')) {
            Schema::table('personnel_awards', function (Blueprint $table): void {
                $table->decimal('amount', 14, 2)->nullable()->after('reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personnel_awards', 'amount')) {
            Schema::table('personnel_awards', function (Blueprint $table): void {
                $table->dropColumn('amount');
            });
        }
    }
};
