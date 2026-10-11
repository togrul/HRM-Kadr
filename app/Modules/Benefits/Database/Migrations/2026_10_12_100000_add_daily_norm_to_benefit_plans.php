<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yemək xərcləri üzrə gündəlik norma (VM m.119.2; DVX izahı 10.07.2026).
 *
 *  - `exempt_daily_norm`: işçi üzrə gündəlik azad məbləğ (AZN); null — norma yoxdur, paket əvvəlki
 *    kimi yalnız `taxable` / `affects_social` bayraqlarına görə işlənir.
 *  - `norm_is_invoice_based`: yemək ictimai iaşə müəssisəsindən elektron qaimə-faktura ilə alınır və
 *    ya işəgötürən özü hazırlayır — məbləğdən asılı olmayaraq nə vergiyə, nə DSMF-ə cəlb olunur.
 *
 * Mövcud yemək kartı paketlərinə norma 5 AZN yazılır. Bu, keçmişə təsir etmir: gəlir sətirləri
 * yalnız cari ayın sinxronunda yenidən hesablanır, ödənilmiş (və ya kilidli aya düşən) sətirlərə
 * toxunulmur — retro «geri ödəniş» yaranmır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('benefit_plans')) {
            return;
        }

        Schema::table('benefit_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('benefit_plans', 'exempt_daily_norm')) {
                $table->decimal('exempt_daily_norm', 10, 2)->nullable()->after('affects_social');
            }

            if (! Schema::hasColumn('benefit_plans', 'norm_is_invoice_based')) {
                $table->boolean('norm_is_invoice_based')->default(false)->after('exempt_daily_norm');
            }
        });

        DB::table('benefit_plans')
            ->where('type', 'meal_card')
            ->whereNull('exempt_daily_norm')
            ->update(['exempt_daily_norm' => 5.00]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('benefit_plans')) {
            return;
        }

        Schema::table('benefit_plans', function (Blueprint $table): void {
            foreach (['norm_is_invoice_based', 'exempt_daily_norm'] as $column) {
                if (Schema::hasColumn('benefit_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
