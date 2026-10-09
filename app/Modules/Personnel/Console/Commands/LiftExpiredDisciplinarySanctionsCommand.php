<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\Punishment;
use App\Support\Database\InstalledTables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Marks disciplinary sanctions whose expiry date has passed as lifted. Runs daily from
 * the scheduler; idempotent — a sanction already lifted is not touched again. Criminal
 * record entries share the table but are not disciplinary sanctions, so they are skipped.
 */
class LiftExpiredDisciplinarySanctionsCommand extends Command
{
    protected $signature = 'personnel:lift-expired-sanctions {--date= : Treat this day (Y-m-d) as today}';

    protected $description = 'Mark disciplinary sanctions whose expiry date has passed as lifted';

    public function handle(): int
    {
        if (! InstalledTables::hasColumn('personnel_punishments', 'lifted_at')) {
            return self::SUCCESS;
        }

        $today = $this->option('date') ? (string) $this->option('date') : today()->toDateString();
        $criminal = Punishment::PUNISHMENT_TYPES['criminal'];

        $lifted = DB::table('personnel_punishments')
            ->whereNull('lifted_at')
            ->whereNotNull('expired_date')
            ->whereDate('expired_date', '<=', $today)
            ->whereNotIn('punishment_id', DB::table('punishments')->where('punishment_type_id', $criminal)->select('id'))
            ->update(['lifted_at' => $today]);

        $this->info(__('personnel::common.sanctions.lifted', ['count' => $lifted]));

        return self::SUCCESS;
    }
}
