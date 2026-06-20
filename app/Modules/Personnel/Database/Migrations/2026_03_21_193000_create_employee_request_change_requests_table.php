<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_request_change_requests')) {
            Schema::create('employee_request_change_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('requestable_type');
                $table->unsignedBigInteger('requestable_id');
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('reason');
                $table->json('proposed_patch')->nullable();
                $table->string('status', 32)->default('pending');
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->dateTime('applied_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('employee_request_change_requests', function (Blueprint $table): void {
            if (! $this->indexExists('employee_request_change_requests', 'emp_req_change_requestable_idx')) {
                $table->index(['requestable_type', 'requestable_id'], 'emp_req_change_requestable_idx');
            }

            if (! $this->indexExists('employee_request_change_requests', 'emp_req_change_personnel_status_idx')) {
                $table->index(['personnel_id', 'status'], 'emp_req_change_personnel_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_request_change_requests');
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
