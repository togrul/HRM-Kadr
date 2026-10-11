<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İcra sənədləri reyestri (docs/payroll-legal-basis.md §10): ƏM m.174–176, «İcra haqqında» Qanun.
 *
 *  - `enforcement_documents`: işçi üzrə icra sənədi — növ (aliment, sağlamlığa vurulmuş ziyan, ailə
 *    başçısını itirmə, cinayətlə vurulmuş ziyan, digər), növbə, sənədi verən (məhkəmə/icra məmuru),
 *    nömrə/tarix, məbləğ (sabit və ya faiz), alıcı, başlama/bitmə, ümumi borc və qalıq;
 *  - `enforcement_deductions`: kilidlənmiş run üzrə faktiki tutulma tarixçəsi (run yenidən açılanda silinir,
 *    qalıq bərpa olunur) — icra məmuruna göndəriləcək siyahının mənbəyi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enforcement_documents')) {
            Schema::create('enforcement_documents', function (Blueprint $table): void {
                $table->id();
                $table->string('tabel_no');
                $table->foreign('tabel_no', 'enf_doc_tabel_fk')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('kind', 30); // alimony | health_damage | breadwinner_loss | crime_damage | other
                $table->unsignedTinyInteger('priority')->default(5);
                $table->string('issuer_type', 30)->default('enforcement_officer'); // court | enforcement_officer | notary | other
                $table->string('issuer_name', 190);
                $table->string('officer_name', 190)->nullable();
                $table->string('document_no', 80);
                $table->date('document_date')->nullable();
                $table->date('received_on');
                $table->string('method', 10)->default('percent'); // fixed | percent
                $table->decimal('fixed_amount', 12, 2)->nullable();
                $table->decimal('percent', 5, 2)->nullable();
                $table->string('recipient_name', 190);
                $table->string('recipient_account', 64)->nullable();
                $table->string('recipient_details', 255)->nullable();
                $table->decimal('total_debt', 12, 2)->nullable();
                $table->decimal('remaining', 12, 2)->nullable();
                $table->char('currency', 3)->default('AZN');
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->string('status', 20)->default('active'); // active | suspended | closed
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tabel_no', 'status'], 'enf_doc_tabel_status_idx');
            });
        }

        if (! Schema::hasTable('enforcement_deductions')) {
            Schema::create('enforcement_deductions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('enforcement_document_id')->constrained('enforcement_documents')->cascadeOnDelete();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
                $table->string('tabel_no');
                $table->decimal('requested_amount', 12, 2);
                $table->decimal('amount', 12, 2);
                $table->decimal('wage_due', 12, 2);
                $table->decimal('remaining_after', 12, 2)->nullable();
                $table->date('paid_on');
                $table->timestamps();

                // Bir sənəd üzrə bir run-da bir tutulma — kilid/yenidən açılma idempotentdir.
                $table->unique(['enforcement_document_id', 'payroll_run_id'], 'enf_ded_doc_run_unique');
                $table->index(['payroll_run_id'], 'enf_ded_run_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_deductions');
        Schema::dropIfExists('enforcement_documents');
    }
};
