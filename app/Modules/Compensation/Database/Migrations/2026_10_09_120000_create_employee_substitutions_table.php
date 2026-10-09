<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Substitution (əvəzetmə) records: an employee performs an absent colleague's duties on
 * top of their own for a period, for extra pay (a percent of salary or a fixed amount).
 * Written by the approved substitution order; the source key ties the row to that order
 * so revoking the order removes exactly this row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_substitutions')) {
            return;
        }

        Schema::create('employee_substitutions', function (Blueprint $table): void {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('substituted_tabel_no')->nullable();
            $table->string('substituted_name')->nullable();
            $table->unsignedBigInteger('substituted_position_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('extra_pay_percent', 5, 2)->nullable();
            $table->decimal('extra_pay_amount', 12, 2)->nullable();
            $table->string('order_no')->nullable();
            $table->string('source_key')->unique();
            $table->timestamps();

            $table->index(['tabel_no', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_substitutions');
    }
};
