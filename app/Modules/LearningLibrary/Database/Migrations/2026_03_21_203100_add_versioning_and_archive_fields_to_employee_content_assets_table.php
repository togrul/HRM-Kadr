<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_content_assets', function (Blueprint $table): void {
            $table->string('version')->default('1.0')->after('content_type');
            $table->string('version_family_key')->nullable()->after('version');
            $table->unsignedBigInteger('previous_version_id')->nullable()->after('version_family_key');
            $table->timestamp('archived_at')->nullable()->after('estimated_minutes');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');

            $table->index('version_family_key');
            $table->index('previous_version_id');
            $table->index('archived_by');
        });
    }

    public function down(): void
    {
        Schema::table('employee_content_assets', function (Blueprint $table): void {
            $table->dropIndex(['version_family_key']);
            $table->dropIndex(['previous_version_id']);
            $table->dropIndex(['archived_by']);
            $table->dropColumn(['version', 'version_family_key', 'previous_version_id', 'archived_at', 'archived_by']);
        });
    }
};
