<?php

use App\Modules\Vacation\Application\Services\LegacyVacationMigrator;
use App\Modules\Vacation\Application\Services\VacationSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * İş ili uçotuna keçid: uçotun başlama tarixi (bu gün) Tənzimləmələrə yazılır və mövcud təqvim
 * ili balansları (`vacations`) iş illərinə köçürülür (LegacyVacationMigrator). Köhnə cədvəl
 * saxlanılır; down() köçürülmüş iş illərini silir və işarələri götürür.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! DB::table('settings')->where('name', VacationSettings::LEDGER_START)->exists()) {
            DB::table('settings')->insert([
                'name' => VacationSettings::LEDGER_START,
                'value' => now()->toDateString(),
                'type' => 'string',
            ]);
        }

        if (Schema::hasTable('settings') && ! DB::table('settings')->where('name', VacationSettings::COMPENSATION_WITHOUT_TERMINATION)->exists()) {
            DB::table('settings')->insert([
                'name' => VacationSettings::COMPENSATION_WITHOUT_TERMINATION,
                'value' => '0',
                'type' => 'string',
            ]);
        }

        $result = app(LegacyVacationMigrator::class)->migrate();

        Log::info('vacation.legacy_balances_migrated', $result);
    }

    public function down(): void
    {
        app(LegacyVacationMigrator::class)->rollback();

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('name', [VacationSettings::LEDGER_START, VacationSettings::COMPENSATION_WITHOUT_TERMINATION])->delete();
        }
    }
};
