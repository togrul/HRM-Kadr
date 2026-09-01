<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('retro_payments')) {
            return;
        }

        Schema::create('retro_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('source_payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('paid_payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->timestamps();

            // A given source period's retro is paid at most once per paying run.
            $table->unique(['tabel_no', 'source_payroll_run_id', 'paid_payroll_run_id'], 'retro_unique_source_paid');
            $table->index(['tabel_no', 'source_payroll_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retro_payments');
    }
};
