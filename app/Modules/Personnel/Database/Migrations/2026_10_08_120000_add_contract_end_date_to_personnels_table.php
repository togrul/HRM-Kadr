<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Müddətli əmək müqaviləsinin bitmə tarixi (ƏM m.47).
 *
 * Bu, işdən faktiki çıxma tarixindən (`leave_work_date`) ayrı anlayışdır: müqavilənin
 * müddəti bitəndə işçi avtomatik işdən azad olunmur — xitam yalnız əmrlə rəsmiləşir və
 * `leave_work_date` yalnız həmin əmrin təsdiqi ilə yazılır. Əvvəllər formadakı
 * "Müqavilənin bitmə tarixi" sahəsi birbaşa `leave_work_date`-ə bağlı idi və keçmiş
 * tarix yeni işçini "işdən ayrılan" edirdi.
 *
 * Mövcud `leave_work_date` dəyərlərinə toxunulmur: həqiqi xitamları formadan yazılmış
 * "bitmə tarixləri"ndən avtomatik ayırmaq mümkün deyil, ona görə onları əl ilə yoxlamaq
 * lazımdır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table): void {
            $table->date('contract_end_date')->nullable()->after('contract_date');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table): void {
            $table->dropColumn('contract_end_date');
        });
    }
};
