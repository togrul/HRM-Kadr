<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredit qrafiki üzrə həmin run-da tutulmalı olan məbləğ (taksit, son haqq-hesabda qalan borc).
 * ƏM m.176.1 həddi (20 %) daha az yer qoyanda `amount` < `scheduled_amount` olur — fərq kreditdə
 * qalır, qrafik uzanır və «Kredit/avans» ekranında görünür. Köhnə sətirlərdə boşdur (= amount).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loan_repayments') || Schema::hasColumn('loan_repayments', 'scheduled_amount')) {
            return;
        }

        Schema::table('loan_repayments', function (Blueprint $table): void {
            $table->decimal('scheduled_amount', 12, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('loan_repayments') || ! Schema::hasColumn('loan_repayments', 'scheduled_amount')) {
            return;
        }

        Schema::table('loan_repayments', function (Blueprint $table): void {
            $table->dropColumn('scheduled_amount');
        });
    }
};
