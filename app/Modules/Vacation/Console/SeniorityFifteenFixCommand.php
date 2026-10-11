<?php

namespace App\Modules\Vacation\Console;

use App\Modules\Vacation\Application\Services\FifteenYearSeniorityFix;
use App\Services\StructureScope;
use Illuminate\Console\Command;

/**
 * «Düz 15 il» düzəlişi (ƏM m.116.1; docs/vacation-legal-basis.md, (b-3)) konsoldan: standart
 * olaraq yalnız önizləmə; `--apply` işləyən işçilərə fərqi düzəliş kimi yazır (idempotent).
 * Bütün strukturlar üzrə işləyir — ekranda isə istifadəçinin struktur görünürlüyü tətbiq olunur.
 */
class SeniorityFifteenFixCommand extends Command
{
    protected $signature = 'vacation:seniority-fifteen-fix
        {--apply : Düzəlişi yaz (olmasa yalnız önizləmə)}';

    protected $description = 'Find work years that got 4 instead of 6 seniority days at exactly 15 years of service and post the difference';

    public function handle(FifteenYearSeniorityFix $fix): int
    {
        $scope = StructureScope::all();
        $preview = $fix->preview($scope);

        $rows = fn (array $list): array => array_map(fn (array $row): array => [
            $row['tabel_no'],
            $row['fullname'],
            $row['sequence'],
            $row['start'].' – '.$row['end'],
            $row['seniority_days'],
            $row['applied'] ? 'yazılıb ('.$row['applied_days'].')' : '+'.$row['missing'],
            $row['leave_date'] ?? '',
        ], $list);

        $headers = ['Tabel', 'İşçi', 'İş ili', 'Dövr', 'Staj əlavəsi', 'Düzəliş', 'Xitam'];
        $this->info('İşləyən işçilər: '.count($preview['active']));
        $this->table($headers, $rows($preview['active']));
        $this->info('Xitam olunmuş işçilər (kompensasiya fərqi, balansa yazılmır): '.count($preview['terminated']));
        $this->table($headers, $rows($preview['terminated']));

        if (! $this->option('apply')) {
            return self::SUCCESS;
        }

        $this->info('Yazılan düzəliş: '.$fix->apply($scope));

        return self::SUCCESS;
    }
}
