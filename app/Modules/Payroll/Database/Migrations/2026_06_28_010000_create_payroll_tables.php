<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique(); // e.g. 2026-06
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->date('starts_on');
                $table->date('ends_on');
                $table->char('currency', 3)->default('AZN');
                $table->string('status')->default('open'); // open | closed
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['year', 'month']);
            });
        }

        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
                $table->foreignId('regime_id')->nullable()->constrained('compensation_regimes')->nullOnDelete();
                $table->string('run_type')->default('regular'); // regular | off_cycle
                $table->string('status')->default('draft'); // draft | calculated | approved | locked
                $table->decimal('gross_total', 14, 2)->default(0);
                $table->decimal('deduction_total', 14, 2)->default(0);
                $table->decimal('net_total', 14, 2)->default(0);
                $table->decimal('employer_total', 14, 2)->default(0);
                $table->unsignedInteger('employee_count')->default(0);
                $table->timestamp('calculated_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['payroll_period_id', 'status']);
            });
        }

        if (! Schema::hasTable('payslips')) {
            Schema::create('payslips', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->string('tabel_no');
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('regime_id')->nullable()->constrained('compensation_regimes')->nullOnDelete();
                $table->decimal('gross', 12, 2)->default(0);
                $table->decimal('total_deductions', 12, 2)->default(0);
                $table->decimal('net', 12, 2)->default(0);
                $table->decimal('employer_cost', 12, 2)->default(0);
                $table->char('currency', 3)->default('AZN');
                $table->string('status')->default('calculated'); // calculated | locked
                $table->json('snapshot')->nullable();
                $table->timestamps();

                $table->index('payroll_run_id');
                $table->index(['tabel_no', 'payroll_run_id']);
            });
        }

        if (! Schema::hasTable('payslip_lines')) {
            Schema::create('payslip_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
                $table->foreignId('component_id')->nullable()->constrained('compensation_components')->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->string('kind'); // earning | deduction | employer
                $table->decimal('amount', 12, 2)->default(0);
                $table->boolean('taxable')->default(false);
                $table->boolean('affects_social')->default(false);
                $table->boolean('is_statutory')->default(false);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index('payslip_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
    }
};
