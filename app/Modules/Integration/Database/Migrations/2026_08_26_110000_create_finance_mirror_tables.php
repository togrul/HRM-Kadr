<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the finance system tells us, kept locally.
 *
 * ## Why mirrored and not queried live
 *
 * An employee opening their payslip must not depend on the finance system being
 * up, and the month-lock guard must give an answer behind a firewall. Both are
 * read constantly and change rarely, which is exactly the shape a mirror suits.
 *
 * ## Kept apart from our own tables
 *
 * `payslips` here belongs to this system's own payroll runs and has its own
 * lifecycle. These rows are somebody else's result. Mixing them would lose
 * track of which figure came from where — and when the two disagreed, nobody
 * could say which was authoritative.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payslips', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('employee_name');
            $table->decimal('gross', 18, 2)->default(0);
            $table->decimal('total_deductions', 18, 2)->default(0);
            $table->decimal('net', 18, 2)->default(0);
            $table->char('currency', 3)->default('AZN');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tabel_no', 'year', 'month'], 'finance_payslips_uq');
            $table->index(['year', 'month'], 'finance_payslips_period_idx');
        });

        Schema::create('finance_period_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->boolean('closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month'], 'finance_period_states_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_period_states');
        Schema::dropIfExists('finance_payslips');
    }
};
