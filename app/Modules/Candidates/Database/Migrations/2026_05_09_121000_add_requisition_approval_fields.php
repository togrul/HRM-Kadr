<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_requisitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('job_requisitions', 'approval_status')) {
                $table->string('approval_status')->default('draft')->after('status')->index();
            }

            if (! Schema::hasColumn('job_requisitions', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('job_requisitions', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('job_requisitions', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (! Schema::hasColumn('job_requisitions', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_requisitions', function (Blueprint $table): void {
            if (Schema::hasColumn('job_requisitions', 'approved_by')) {
                $table->dropForeign(['approved_by']);
            }

            if (Schema::hasColumn('job_requisitions', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
            }

            foreach (['approval_note', 'rejected_at', 'rejected_by', 'approved_by', 'approval_status'] as $column) {
                if (Schema::hasColumn('job_requisitions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
