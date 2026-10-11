<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qaimə-fakturalı iaşə işçi səviyyəsində: eyni yemək paketində bəzi işçilər ictimai iaşədən
 * elektron qaimə-faktura ilə (və ya işəgötürənin hazırladığı yeməklə), digərləri kartla alırsa,
 * qoşulmada `invoice_based` qeyd olunur — həmin işçinin işəgötürən payı məbləğdən asılı olmayaraq
 * nə vergiyə, nə DSMF-ə cəlb olunur (DVX izahı 10.07.2026). Paket səviyyəli bayraq
 * (`benefit_plans.norm_is_invoice_based`) hamıya aiddir və dəyişmir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('benefit_enrollments') || Schema::hasColumn('benefit_enrollments', 'invoice_based')) {
            return;
        }

        Schema::table('benefit_enrollments', function (Blueprint $table): void {
            $table->boolean('invoice_based')->default(false)->after('employer_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('benefit_enrollments') || ! Schema::hasColumn('benefit_enrollments', 'invoice_based')) {
            return;
        }

        Schema::table('benefit_enrollments', function (Blueprint $table): void {
            $table->dropColumn('invoice_based');
        });
    }
};
