<?php

use App\Modules\Orders\Infrastructure\Document\DisciplinarySanctionTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ƏM Md.190.1: intizam tənbehi verildiyi gündən altı ay qüvvədə olur. Əvvəlki
     * standart 12 ay idi. Ayar 6-dan böyükdürsə 6-ya endirilir; hələ götürülməmiş
     * tənbehlərin bitmə tarixi verilmə tarixi + 6 ayı keçirsə, ona çəkilir (artıq
     * keçmiş tarixlər növbəti `personnel:lift-expired-sanctions` icrasında götürülür).
     * Qısaldılmış (daha əlverişli) müddətlərə toxunulmur.
     */
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $value = (int) DB::table('settings')->where('name', DisciplinarySanctionTerm::SETTING)->value('value');

            if ($value > DisciplinarySanctionTerm::LEGAL_MONTHS) {
                DB::table('settings')
                    ->where('name', DisciplinarySanctionTerm::SETTING)
                    ->update(['value' => (string) DisciplinarySanctionTerm::LEGAL_MONTHS]);
            }
        }

        if (! Schema::hasTable('personnel_punishments') || ! Schema::hasColumn('personnel_punishments', 'lifted_at')) {
            return;
        }

        DB::table('personnel_punishments')
            ->whereNull('lifted_at')
            ->whereNotNull('given_date')
            ->whereNotNull('expired_date')
            ->whereNotNull('order_no')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $legal = Carbon::parse((string) $row->given_date)
                        ->addMonthsNoOverflow(DisciplinarySanctionTerm::LEGAL_MONTHS)
                        ->toDateString();

                    if ((string) $row->expired_date > $legal) {
                        DB::table('personnel_punishments')->where('id', $row->id)->update(['expired_date' => $legal]);
                    }
                }
            });
    }

    /** Qanuna uyğunlaşdırma geri qaytarılmır: 12 aylıq müddət qanunsuz idi. */
    public function down(): void {}
};
