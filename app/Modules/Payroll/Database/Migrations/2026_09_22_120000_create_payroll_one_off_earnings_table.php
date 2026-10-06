<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-off earnings other modules hand to payroll (KPI bonus, monetary award): paid in
 * the regular run of their pay month, keyed by the sender's own id so a repeat hand-off
 * never pays twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_one_off_earnings')) {
            return;
        }

        Schema::create('payroll_one_off_earnings', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no', 64)->index();
            $table->string('code', 40);
            $table->string('name');
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('pay_year');
            $table->unsignedTinyInteger('pay_month');
            $table->boolean('taxable')->default(true);
            $table->boolean('affects_social')->default(true);
            $table->string('source_key', 120)->unique();
            $table->unsignedBigInteger('paid_payroll_run_id')->nullable();
            $table->timestamps();

            $table->index(['tabel_no', 'pay_year', 'pay_month'], 'payroll_one_off_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_one_off_earnings');
    }
};
