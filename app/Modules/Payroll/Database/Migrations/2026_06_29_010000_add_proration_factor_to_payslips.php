<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payslips') && ! Schema::hasColumn('payslips', 'proration_factor')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->decimal('proration_factor', 5, 4)->default(1)->after('employer_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payslips') && Schema::hasColumn('payslips', 'proration_factor')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->dropColumn('proration_factor');
            });
        }
    }
};
