<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İmtiyazlar modulu: paket kataloqu (uyğunluq qaydaları ilə), işçi qoşulmaları, qoşulmaya
 * daxil edilmiş ailə üzvləri və illik yenilənmə (seçim) pəncərələri.
 *
 * Məbləğlər qoşulma anında dondurulur (employee_amount / employer_amount) — paketin qiyməti
 * sonradan dəyişəndə keçmiş aylar və xərc hesabatı dəyişmir; yeni qiymət yenilənmədə tətbiq olunur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('benefit_plans')) {
            Schema::create('benefit_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name');
                $table->string('type', 40)->index();
                $table->text('description')->nullable();
                $table->string('provider')->nullable();
                $table->char('currency', 3)->default('AZN');
                $table->decimal('monthly_cost', 12, 2)->default(0);
                $table->decimal('employer_share_percent', 5, 2)->default(100);
                $table->boolean('allows_dependents')->default(false);
                $table->decimal('dependent_monthly_cost', 12, 2)->default(0);
                $table->decimal('dependent_employer_share_percent', 5, 2)->default(0);
                $table->unsignedSmallInteger('max_dependents')->nullable();
                $table->boolean('taxable')->default(true);
                $table->boolean('affects_social')->default(true);
                $table->string('tax_basis', 60)->nullable();
                $table->date('contract_start')->nullable();
                $table->date('contract_end')->nullable();
                $table->unsignedSmallInteger('renewal_window_days')->default(30);
                $table->boolean('auto_renew')->default(true);
                $table->boolean('self_service')->default(true);
                $table->json('eligibility')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('benefit_renewal_windows')) {
            Schema::create('benefit_renewal_windows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('benefit_plan_id')->constrained('benefit_plans')->cascadeOnDelete();
                $table->date('contract_end');
                $table->date('next_contract_end');
                $table->date('opens_on');
                $table->date('closes_on');
                $table->string('status', 20)->default('open');
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->unique(['benefit_plan_id', 'contract_end'], 'benefit_renewal_plan_term_unique');
                $table->index(['status', 'closes_on']);
            });
        }

        if (! Schema::hasTable('benefit_enrollments')) {
            Schema::create('benefit_enrollments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('benefit_plan_id')->constrained('benefit_plans')->cascadeOnDelete();
                $table->string('tabel_no');
                $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('status', 20)->default('active');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('end_reason', 30)->nullable();
                $table->date('end_date_before_termination')->nullable();
                $table->string('source', 20)->default('hr');
                $table->decimal('employee_amount', 12, 2)->default(0);
                $table->decimal('employer_amount', 12, 2)->default(0);
                $table->char('currency', 3)->default('AZN');
                $table->boolean('eligibility_overridden')->default(false);
                $table->string('renewal_decision', 20)->nullable();
                $table->foreignId('renewal_window_id')->nullable()->constrained('benefit_renewal_windows')->nullOnDelete();
                $table->unsignedBigInteger('renewal_of_id')->nullable()->index();
                $table->unsignedBigInteger('enrolled_by')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['tabel_no', 'status']);
                $table->index(['benefit_plan_id', 'status']);
            });
        }

        if (! Schema::hasTable('benefit_enrollment_dependents')) {
            Schema::create('benefit_enrollment_dependents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('benefit_enrollment_id')->constrained('benefit_enrollments')->cascadeOnDelete();
                $table->unsignedBigInteger('personnel_kinship_id')->nullable()->index();
                $table->string('full_name');
                $table->string('relation')->nullable();
                $table->date('birthdate')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Modul geri alınanda onun Payroll girişləri də qalmasın: işçi payı sətirləri, ödənilməmiş
        // natura gəliri sətirləri və (istifadə olunmursa) işçi payı komponenti.
        if (Schema::hasTable('employee_compensation_lines')) {
            DB::table('employee_compensation_lines')->where('note', 'like', 'benefits#%')->delete();
        }

        if (Schema::hasTable('payroll_one_off_earnings')) {
            DB::table('payroll_one_off_earnings')->where('source_key', 'like', 'benefits:%')->whereNull('paid_payroll_run_id')->delete();
        }

        if (Schema::hasTable('compensation_components')) {
            $componentId = DB::table('compensation_components')->where('code', 'benefit_employee_share')->value('id');

            if ($componentId !== null && ! DB::table('employee_compensation_lines')->where('component_id', $componentId)->exists()) {
                DB::table('compensation_components')->where('id', $componentId)->delete();
            }
        }

        Schema::dropIfExists('benefit_enrollment_dependents');
        Schema::dropIfExists('benefit_enrollments');
        Schema::dropIfExists('benefit_renewal_windows');
        Schema::dropIfExists('benefit_plans');
    }
};
