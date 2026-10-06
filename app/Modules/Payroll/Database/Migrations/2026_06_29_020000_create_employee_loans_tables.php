<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_loans')) {
            Schema::create('employee_loans', function (Blueprint $table): void {
                $table->id();
                $table->string('tabel_no');
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('type')->default('loan'); // loan | advance
                $table->decimal('principal', 12, 2);
                $table->decimal('monthly_installment', 12, 2);
                $table->decimal('remaining', 12, 2);
                $table->char('currency', 3)->default('AZN');
                $table->string('status')->default('active'); // active | closed
                $table->date('start_on');
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['tabel_no', 'status']);
            });
        }

        if (! Schema::hasTable('loan_repayments')) {
            Schema::create('loan_repayments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
                $table->foreignId('payroll_run_id')->nullable()->constrained('payroll_runs')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('paid_on');
                $table->timestamps();

                // One repayment per loan per run — keeps lock/reopen idempotent.
                $table->unique(['employee_loan_id', 'payroll_run_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('employee_loans');
    }
};
