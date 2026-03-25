<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_document_templates', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('requires_acknowledgement');
            $table->boolean('auto_assign_new_hires')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_document_templates', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'auto_assign_new_hires']);
        });
    }
};
