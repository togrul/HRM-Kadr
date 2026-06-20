<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_documents', function (Blueprint $table): void {
            $table->string('employee_visibility', 32)->default('visible')->after('filename');
            $table->timestamp('visible_from')->nullable()->after('employee_visibility');
            $table->timestamp('visible_until')->nullable()->after('visible_from');
            $table->index(['employee_visibility', 'visible_from', 'visible_until'], 'personnel_docs_employee_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::table('personnel_documents', function (Blueprint $table): void {
            $table->dropIndex('personnel_docs_employee_visibility_idx');
            $table->dropColumn(['employee_visibility', 'visible_from', 'visible_until']);
        });
    }
};
