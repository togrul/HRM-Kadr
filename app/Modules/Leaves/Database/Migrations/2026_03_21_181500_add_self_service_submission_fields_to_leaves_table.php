<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            $table->string('submission_source', 32)->nullable()->after('assigned_to');
            $table->foreignId('submitted_by_user_id')->nullable()->after('submission_source')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropColumn('submission_source');
        });
    }
};
