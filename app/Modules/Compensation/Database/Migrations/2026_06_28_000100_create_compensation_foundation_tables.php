<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compensation_regimes')) {
            Schema::create('compensation_regimes', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pay_scales')) {
            Schema::create('pay_scales', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->foreignId('regime_id')->constrained('compensation_regimes')->cascadeOnDelete();
                $table->char('currency', 3)->default('AZN');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['regime_id', 'effective_from']);
            });
        }

        if (! Schema::hasTable('pay_grades')) {
            Schema::create('pay_grades', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pay_scale_id')->constrained('pay_scales')->cascadeOnDelete();
                $table->string('code');
                $table->string('name');
                $table->decimal('base_amount', 12, 2)->default(0);
                // rank_categories.id and positions.id are signed integers — match the type for the FK.
                $table->integer('rank_category_id')->nullable();
                $table->foreign('rank_category_id')->references('id')->on('rank_categories')->nullOnDelete();
                $table->integer('position_id')->nullable();
                $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['pay_scale_id', 'sort']);
            });
        }

        if (! Schema::hasTable('compensation_components')) {
            Schema::create('compensation_components', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('type')->default('earning'); // earning | deduction
                $table->string('calc_type')->default('fixed'); // fixed | percent | formula | per_diem | rate
                $table->boolean('taxable')->default(true);
                $table->boolean('affects_social')->default(true);
                $table->boolean('is_statutory')->default(false);
                $table->string('gl_code')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_compensations')) {
            Schema::create('employee_compensations', function (Blueprint $table): void {
                $table->id();
                $table->string('tabel_no');
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('regime_id')->constrained('compensation_regimes')->cascadeOnDelete();
                $table->foreignId('pay_grade_id')->nullable()->constrained('pay_grades')->nullOnDelete();
                $table->decimal('base_amount', 12, 2)->default(0);
                $table->char('currency', 3)->default('AZN');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->string('status')->default('draft'); // draft | active | ended
                $table->string('order_no')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['tabel_no', 'effective_from']);
                $table->index(['tabel_no', 'status']);
            });
        }

        if (! Schema::hasTable('employee_compensation_lines')) {
            Schema::create('employee_compensation_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_compensation_id')->constrained('employee_compensations')->cascadeOnDelete();
                $table->foreignId('component_id')->constrained('compensation_components')->cascadeOnDelete();
                $table->decimal('amount', 12, 2)->nullable();
                $table->decimal('percent', 5, 2)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index('employee_compensation_id');
            });
        }

        if (! Schema::hasTable('employee_bank_accounts')) {
            Schema::create('employee_bank_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('tabel_no');
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('iban');
                $table->string('bank_name')->nullable();
                $table->string('account_no')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tabel_no', 'is_primary']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_compensation_lines');
        Schema::dropIfExists('employee_compensations');
        Schema::dropIfExists('compensation_components');
        Schema::dropIfExists('pay_grades');
        Schema::dropIfExists('pay_scales');
        Schema::dropIfExists('compensation_regimes');
    }
};
