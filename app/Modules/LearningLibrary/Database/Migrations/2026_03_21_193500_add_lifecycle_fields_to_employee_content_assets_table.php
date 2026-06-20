<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_content_assets', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('visibility');
            $table->boolean('auto_assign_new_hires')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('employee_content_assets', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'auto_assign_new_hires']);
        });
    }
};
