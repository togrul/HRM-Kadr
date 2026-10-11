<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İşçi üzrə gəlir vergisi güzəşti kartı — Vergi Məcəlləsi m.102.1-1 (800), 102.2 (400), 102.3 (200),
 * 102.4 (100), 102.5 (50) (docs/payroll-legal-basis.md §9).
 *
 *  - `employee_tax_reliefs`: işçinin hüququ — kateqoriya, təsdiqedici sənəd (nömrə, tarix, verən orqan,
 *    fayl — özəl diskdə), statusun yarandığı tarix, sənədlərin təqdim edildiyi tarix (m.102.8),
 *    etibarlılıq sonu, qanuni başlanğıc (`legal_from`, m.102.8) və əmək haqqında tətbiqin başladığı tarix
 *    (`payroll_from`: kilidlənmiş — ödənilmiş — aylardan sonra; aradakı aylar «Vergi düzəliş hesabatı»nda).
 *  - Məbləğlər kodda deyil, `statutory_exemptions`-dadır (növ `personal`) — mühasib effektiv tarixlə
 *    dəyişə bilər. Seed sətri yalnız eyni kodlu sətir yoxdursa yazılır.
 */
return new class extends Migration
{
    /**
     * code => [legal_basis, amount, qüvvəyə minmə]. 102.1-1 05.12.2023 tarixli 1033-VIQD ilə əlavə
     * olunub (2024-dən); qalanları 2019-dan əvvəl də qüvvədə idi — sistemin hesablama dövrü üçün kifayətdir.
     *
     * @var array<string, array{0: string, 1: int, 2: string}>
     */
    private const PERSONAL = [
        'vm_102_1_1' => ['VM 102.1-1', 800, '2024-01-01'],
        'vm_102_2' => ['VM 102.2', 400, '2019-01-01'],
        'vm_102_3' => ['VM 102.3', 200, '2019-01-01'],
        'vm_102_4' => ['VM 102.4', 100, '2019-01-01'],
        'vm_102_5' => ['VM 102.5', 50, '2019-01-01'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('employee_tax_reliefs')) {
            Schema::create('employee_tax_reliefs', function (Blueprint $table): void {
                $table->id();
                $table->string('tabel_no');
                $table->foreign('tabel_no', 'emp_tax_relief_tabel_fk')->references('tabel_no')->on('personnels')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('category', 20);
                $table->string('basis_detail', 160)->nullable();
                $table->string('document_no', 80);
                $table->date('document_date')->nullable();
                $table->string('document_issuer', 160)->nullable();
                $table->string('document_path')->nullable();
                $table->string('document_disk', 20)->nullable();
                $table->string('document_name')->nullable();
                $table->date('status_from');
                $table->date('submitted_on');
                $table->date('valid_to')->nullable();
                $table->date('legal_from');
                $table->date('payroll_from');
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tabel_no', 'payroll_from'], 'emp_tax_relief_tabel_from_idx');
            });
        }

        if (! Schema::hasTable('statutory_exemptions')) {
            return;
        }

        $now = now();

        foreach (self::PERSONAL as $code => [$basis, $amount, $from]) {
            if (DB::table('statutory_exemptions')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('statutory_exemptions')->insert([
                'regime_id' => null,
                'code' => $code,
                'legal_basis' => $basis,
                'kind' => 'personal',
                'component_code' => 'income_tax',
                'amount' => $amount,
                'income_up_to' => null,
                'main_workplace_only' => true,
                'component_codes' => null,
                'effective_from' => $from,
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('statutory_exemptions')) {
            DB::table('statutory_exemptions')->where('kind', 'personal')->whereIn('code', array_keys(self::PERSONAL))->delete();
        }

        Schema::dropIfExists('employee_tax_reliefs');
    }
};
