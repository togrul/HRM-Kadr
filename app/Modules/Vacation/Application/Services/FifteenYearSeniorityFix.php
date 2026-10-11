<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\Personnel;
use App\Models\VacationBalanceEntry;
use App\Models\VacationWorkYear;
use App\Services\StructureScope;
use App\Services\Vacation\Entitlement\CivilEntitlementStrategy;
use App\Services\Vacation\VacationBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * «Düz 15 il» düzəlişi (docs/vacation-legal-basis.md, (b-3)). 11.10.2026-dan əvvəl başlamış iş
 * illərində ümumi stajı iş ilinin başlanğıcında DÜZ 15 il olan işçiyə ƏM m.116.1 üzrə 6 gün
 * əvəzinə 4 gün hesablanıb (köhnə «15 ildən çox» oxunuşu). Norma versiyası geriyə dəyişmir —
 * fərq iş ilinə «düzəliş» (adjustment) hərəkəti ilə yazılır:
 *   - tapılır: mülki işçinin ümumi iş ili, hesablanmış (köçürülmüş deyil), m.118–121 istisnası
 *     yoxdur, staj iş ilinin başlanğıcında düz 15 il, staj əlavəsi qanuni 6 gündən azdır;
 *   - bitmiş iş illəri də daxildir — istifadə olunmamış məzuniyyət yanmır (m.134–135, 144.2);
 *   - işləyən işçiyə fərq yazılır; xitam olunmuş işçi ayrıca siyahıdadır (kompensasiya fərqi
 *     mühasibatlıq tərəfindən kompensasiya əmri ilə — m.144.2, KMQ16), avtomatik yazılmır;
 *   - idempotent: hər iş ilinin hərəkəti sabit `source` açarı ilə bir dəfə yazılır;
 *   - hər yazı audit jurnalına düşür (log adı `vacation`).
 */
class FifteenYearSeniorityFix
{
    public const YEARS = 15;

    public const SOURCE_PREFIX = 'seniority15-fix:';

    /** KM Plenumunun 18.02.2026 qərarı (KMQ16): xitamda əlavə məzuniyyətlər də kompensasiya olunur. */
    public const KMQ16_DATE = '2026-02-18';

    public function __construct(
        private readonly VacationBalanceService $balances,
        private readonly CivilEntitlementStrategy $civil,
    ) {}

    /** Qanuni «15 il və daha çox» pilləsinin günləri (ƏM m.116.1). */
    public static function lawDays(): int
    {
        return VacationNormDefaults::seniorityLaw()['seniority_15']['days'];
    }

    public static function sourceFor(string $tabelNo, int $sequence): string
    {
        return self::SOURCE_PREFIX.$tabelNo.':'.$sequence;
    }

    /**
     * Təsirlənən iş illəri: `active` — işləyənlər (düzəliş yazılır), `terminated` — xitam olunmuşlar
     * (yalnız məlumat). Hər sətirdə `applied` — düzəliş artıq yazılıbmı.
     *
     * @return array{active: list<array<string, mixed>>, terminated: list<array<string, mixed>>}
     */
    public function preview(StructureScope $scope, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $result = ['active' => [], 'terminated' => []];

        $scope->constrain(Personnel::query(), 'structure_id')
            ->with('structure:id,name')
            ->whereNotNull('join_work_date')
            ->orderBy('id')
            ->chunkById(200, function ($people) use (&$result, $today): void {
                foreach ($people as $personnel) {
                    foreach ($this->rowsFor($personnel, $today) as $row) {
                        $result[$row['terminated'] ? 'terminated' : 'active'][] = $row;
                    }
                }
            });

        return $result;
    }

    /**
     * İşləyən işçilərə hələ yazılmamış düzəlişləri yazır; yazılan iş illərinin sayını qaytarır.
     * $tabelNos verilibsə yalnız həmin işçilər (önizləmədə göstərilənlər).
     *
     * @param  list<string>|null  $tabelNos
     */
    public function apply(StructureScope $scope, ?int $userId = null, ?array $tabelNos = null, ?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $written = 0;

        $query = $scope->constrain(Personnel::query(), 'structure_id')->whereNotNull('join_work_date');

        if ($tabelNos !== null) {
            $query->whereIn('tabel_no', $tabelNos === [] ? [''] : $tabelNos);
        }

        $query->orderBy('id')->chunkById(200, function ($people) use (&$written, $today, $userId): void {
            foreach ($people as $personnel) {
                foreach ($this->rowsFor($personnel, $today) as $row) {
                    if ($row['terminated'] || $row['applied']) {
                        continue;
                    }

                    $written += $this->write($personnel, $row, $today, $userId) ? 1 : 0;
                }
            }
        });

        return $written;
    }

    /**
     * Bir işçinin təsirlənən iş illəri.
     *
     * @return list<array<string, mixed>>
     */
    public function rowsFor(Personnel $personnel, CarbonImmutable $today): array
    {
        if ($this->balances->strategyFor($personnel)->key() !== CivilEntitlementStrategy::KEY) {
            return [];
        }

        $this->civil->forget();
        $sequences = [];

        foreach ($this->balances->periodsFor($personnel, $today) as $period) {
            if ($this->civil->compareSeniorityTo($personnel, self::YEARS, $period->start) === 0) {
                $sequences[] = $period->sequence;
            }
        }

        if ($sequences === []) {
            return [];
        }

        $leave = filled($personnel->getAttribute('leave_work_date'))
            ? CarbonImmutable::parse((string) $personnel->getRawOriginal('leave_work_date'))->startOfDay()
            : null;
        $terminated = $leave !== null && $leave->lte($today);
        $rows = [];

        foreach ($this->balances->workYears($personnel, $today) as $balance) {
            if ($balance->isConditions() || $balance->isImported() || ! in_array($balance->period->sequence, $sequences, true)) {
                continue;
            }

            $breakdown = $balance->breakdown;

            if ($breakdown->exclusive || $breakdown->strategy !== CivilEntitlementStrategy::KEY) {
                continue;
            }

            $source = self::sourceFor((string) $personnel->tabel_no, $balance->period->sequence);
            $applied = (int) VacationBalanceEntry::query()->where('tabel_no', $personnel->tabel_no)->where('source', $source)->sum('days');
            $missing = self::lawDays() - $breakdown->seniority;

            if ($missing <= 0 && $applied === 0) {
                continue;
            }

            $rows[] = [
                'tabel_no' => (string) $personnel->tabel_no,
                'fullname' => $personnel->fullname,
                'structure' => $personnel->structure?->name,
                'sequence' => $balance->period->sequence,
                'start' => $balance->period->start->toDateString(),
                'end' => $balance->period->end->toDateString(),
                'finished' => $balance->period->end->lt($today),
                'seniority_days' => $breakdown->seniority,
                'missing' => max(0, $missing),
                'applied' => $applied > 0,
                'applied_days' => $applied,
                'terminated' => $terminated,
                'leave_date' => $leave?->toDateString(),
                'before_kmq16' => $leave !== null && $leave->toDateString() < self::KMQ16_DATE,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function write(Personnel $personnel, array $row, CarbonImmutable $today, ?int $userId): bool
    {
        return DB::transaction(function () use ($personnel, $row, $today, $userId): bool {
            $rows = $this->balances->materialize($personnel, $today);
            $workYear = $rows->get(VacationWorkYear::KIND_ANNUAL.':'.$row['sequence']);

            if (! $workYear instanceof VacationWorkYear) {
                return false;
            }

            // Eyni iş ilinə paralel yazının qarşısı: sətir kilidlənir, sonra mövcudluq yoxlanır.
            VacationWorkYear::query()->whereKey($workYear->id)->lockForUpdate()->first();
            $source = self::sourceFor((string) $personnel->tabel_no, (int) $row['sequence']);

            if (VacationBalanceEntry::query()->where('tabel_no', $personnel->tabel_no)->where('source', $source)->exists()) {
                return false;
            }

            $entry = VacationBalanceEntry::query()->create([
                'work_year_id' => $workYear->id,
                'tabel_no' => $personnel->tabel_no,
                'kind' => VacationBalanceEntry::KIND_ADJUSTMENT,
                'days' => (int) $row['missing'],
                'source' => $source,
                'note' => __('vacation::seniority_fix.entry_note', ['days' => $row['missing'], 'law' => self::lawDays()]),
                'created_by' => $userId,
            ]);

            activity('vacation')
                ->performedOn($entry)
                ->causedBy($userId)
                ->event('seniority_fifteen_fix')
                ->withProperties([
                    'tabel_no' => (string) $personnel->tabel_no,
                    'work_year' => (int) $row['sequence'],
                    'starts_on' => $row['start'],
                    'ends_on' => $row['end'],
                    'seniority_days_before' => (int) $row['seniority_days'],
                    'days' => (int) $row['missing'],
                    'legal_basis' => 'ƏM m.116.1; docs/vacation-legal-basis.md (b-3)',
                ])
                ->log('seniority_fifteen_fix');

            return true;
        });
    }
}
