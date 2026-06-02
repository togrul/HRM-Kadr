<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->foreignId('personnel_id')
                ->nullable()
                ->after('hired_at')
                ->constrained('personnels')
                ->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('personnel_id');
            $table->foreignId('converted_by')
                ->nullable()
                ->after('converted_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['personnel_id', 'converted_at'], 'candidate_applications_personnel_conversion_idx');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->dropIndex('candidate_applications_personnel_conversion_idx');
            $table->dropConstrainedForeignId('converted_by');
            $table->dropConstrainedForeignId('personnel_id');
            $table->dropColumn('converted_at');
        });
    }
};
