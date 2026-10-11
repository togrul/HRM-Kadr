<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\User;
use App\Modules\PerformanceEvaluation\Support\PerformanceStructureScope;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Karyera hazırlıq skoru (0–100): sadə, izah edilə bilən çəkili cəm.
 *
 * Hər komponent 0–100 arası normallaşdırılır, sonra çəkisinə vurulur:
 *  - performance — son qiymətləndirmə formasının yekun balı / şkala həddi;
 *  - training    — son N ildə tamamlanmış təlim sayı / hədəf say;
 *  - kpi         — son təsdiqlənmiş/bağlanmış KPI kartının balı (kalibrlənmiş varsa o) / tam bal həddi;
 *  - portfolio   — təsdiqlənmiş portfolio qeydləri (tədbir, layihə, media) / hədəf say;
 *  - tenure      — staj (il) / hədəf il.
 *
 * Çəkilər config('strategic_hr.career_readiness.weights')-dədir; cəmi 100 olmasa, nisbətə görə
 * normallaşdırılır. Məlumatı olmayan komponent 0 bal verir və «məlumat yoxdur» kimi göstərilir.
 * Hər komponent bütün işçilər üçün bir sorğu ilə oxunur (N+1 yoxdur). Tarixçə saxlanmır —
 * yalnız cari skor hesablanır.
 */
class CareerReadinessScoreService
{
    public const COMPONENTS = ['performance', 'training', 'kpi', 'portfolio', 'tenure'];

    /** KPI kartının yekun sayıldığı statuslar. */
    private const FINAL_SCORECARD_STATUSES = ['approved', 'closed'];

    /** Portfolio qeydləri: cədvəl => təsdiqlənmiş status. */
    private const PORTFOLIO_TABLES = ['personnel_event_records', 'personnel_project_records', 'personnel_media_mentions'];

    /**
     * Baxan verilibsə, yalnız onun struktur görünürlüyündəki işçilər hesablanır.
     *
     * @param  iterable<int|string>  $personnelIds
     * @return array<int, array{score: int, band: string, components: list<array{key: string, value: float|null, display: string, normalized: int, weight: float, contribution: float}>}>
     */
    public function scoresFor(iterable $personnelIds, ?User $viewer = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($personnelIds) ? $personnelIds : iterator_to_array($personnelIds)))));

        if ($ids === []) {
            return [];
        }

        $people = DB::table('personnels')->whereIn('personnels.id', $ids)->whereNull('personnels.deleted_at');
        if ($viewer !== null) {
            PerformanceStructureScope::onPersonnelTable($people, $viewer);
        }
        $joinDates = $people->pluck('personnels.join_work_date', 'personnels.id')->all();
        $ids = array_map('intval', array_keys($joinDates));

        if ($ids === []) {
            return [];
        }

        $weights = $this->weights();
        $raw = [
            'performance' => $this->performance($ids),
            'training' => $this->training($ids),
            'kpi' => $this->kpi($ids),
            'portfolio' => $this->portfolio($ids),
            'tenure' => $this->tenure($joinDates),
        ];

        $scores = [];
        foreach ($ids as $id) {
            $components = [];
            $total = 0.0;

            foreach (self::COMPONENTS as $key) {
                if (($weights[$key] ?? 0.0) <= 0) {
                    continue;
                }

                $value = $raw[$key][$id] ?? null;
                $normalized = $this->normalize($key, $value);
                $contribution = round($normalized * $weights[$key] / 100, 1);
                $total += $contribution;

                $components[] = [
                    'key' => $key,
                    'value' => $value,
                    'display' => $this->display($key, $value),
                    'normalized' => $normalized,
                    'weight' => round($weights[$key], 1),
                    'contribution' => $contribution,
                ];
            }

            $score = (int) max(0, min(100, round($total)));
            $scores[$id] = ['score' => $score, 'band' => $this->band($score), 'components' => $components];
        }

        return $scores;
    }

    /**
     * Konfiqdəki çəkilər faizə (cəmi 100) çevrilir; mənfi çəki 0 sayılır.
     *
     * @return array<string, float>
     */
    public function weights(): array
    {
        $configured = (array) config('strategic_hr.career_readiness.weights', []);
        $weights = [];

        foreach (self::COMPONENTS as $key) {
            $weights[$key] = max(0.0, (float) ($configured[$key] ?? 0));
        }

        $sum = array_sum($weights);

        return $sum > 0
            ? array_map(fn (float $weight): float => $weight / $sum * 100, $weights)
            : array_fill_keys(self::COMPONENTS, 0.0);
    }

    public function band(int $score): string
    {
        $bands = (array) config('strategic_hr.career_readiness.bands', []);

        return match (true) {
            $score >= (int) ($bands['ready'] ?? 75) => 'ready',
            $score >= (int) ($bands['developing'] ?? 50) => 'developing',
            default => 'early',
        };
    }

    private function normalize(string $key, ?float $value): int
    {
        if ($value === null) {
            return 0;
        }

        $target = match ($key) {
            'performance' => (float) config('strategic_hr.career_readiness.performance_scale_max', 100),
            'kpi' => (float) config('strategic_hr.career_readiness.kpi_full_at', 100),
            'training' => (float) config('strategic_hr.career_readiness.training_target_count', 4),
            'portfolio' => (float) config('strategic_hr.career_readiness.portfolio_target_count', 5),
            'tenure' => (float) config('strategic_hr.career_readiness.tenure_target_years', 5),
            default => 100.0,
        };

        if ($target <= 0) {
            return 0;
        }

        return (int) round(max(0.0, min(100.0, $value / $target * 100)));
    }

    private function display(string $key, ?float $value): string
    {
        if ($value === null) {
            return __('performance_evaluation::succession.readiness_score.no_data');
        }

        return match ($key) {
            'training' => __('performance_evaluation::succession.readiness_score.values.training', [
                'count' => (int) $value,
                'years' => (int) config('strategic_hr.career_readiness.training_lookback_years', 3),
            ]),
            'portfolio' => __('performance_evaluation::succession.readiness_score.values.portfolio', ['count' => (int) $value]),
            'tenure' => __('performance_evaluation::succession.readiness_score.values.tenure', ['years' => number_format($value, 1, '.', '')]),
            default => __('performance_evaluation::succession.readiness_score.values.score', ['score' => number_format($value, 1, '.', '')]),
        };
    }

    /**
     * Hər işçinin ən son qiymətləndirilmiş formasının yekun balı.
     *
     * @param  list<int>  $ids
     * @return array<int, float>
     */
    private function performance(array $ids): array
    {
        if (! InstalledTables::has('performance_forms')) {
            return [];
        }

        $latest = [];
        DB::table('performance_forms')
            ->whereIn('personnel_id', $ids)
            ->whereNotNull('final_score')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['personnel_id', 'final_score'])
            ->each(function (object $row) use (&$latest): void {
                $latest[(int) $row->personnel_id] ??= (float) $row->final_score;
            });

        return $latest;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, float>
     */
    private function training(array $ids): array
    {
        if (! InstalledTables::has('training_delivery_records')) {
            return [];
        }

        $since = CarbonImmutable::now()->subYears((int) config('strategic_hr.career_readiness.training_lookback_years', 3));

        return DB::table('training_delivery_records')
            ->whereIn('personnel_id', $ids)
            ->where('result_status', 'completed')
            ->where('completed_at', '>=', $since->toDateTimeString())
            ->groupBy('personnel_id')
            ->selectRaw('personnel_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'personnel_id')
            ->mapWithKeys(fn (mixed $count, int|string $personnelId): array => [(int) $personnelId => (float) $count])
            ->all();
    }

    /**
     * Hər işçinin ən son yekun (təsdiqlənmiş/bağlanmış) KPI kartının balı.
     *
     * @param  list<int>  $ids
     * @return array<int, float>
     */
    private function kpi(array $ids): array
    {
        if (! InstalledTables::has('performance_scorecards')) {
            return [];
        }

        $latest = [];
        DB::table('performance_scorecards')
            ->whereIn('personnel_id', $ids)
            ->whereIn('status', self::FINAL_SCORECARD_STATUSES)
            ->where(fn ($query) => $query->whereNotNull('calibrated_score')->orWhereNotNull('final_score'))
            ->orderByDesc('valid_to')
            ->orderByDesc('id')
            ->get(['personnel_id', 'calibrated_score', 'final_score'])
            ->each(function (object $row) use (&$latest): void {
                $latest[(int) $row->personnel_id] ??= (float) ($row->calibrated_score ?? $row->final_score);
            });

        return $latest;
    }

    /**
     * Təsdiqlənmiş portfolio qeydlərinin sayı — bütün mənbələr bir UNION ALL sorğusunda.
     *
     * @param  list<int>  $ids
     * @return array<int, float>
     */
    private function portfolio(array $ids): array
    {
        $union = null;

        foreach (self::PORTFOLIO_TABLES as $table) {
            if (! InstalledTables::has($table)) {
                continue;
            }

            $part = DB::table($table)
                ->whereIn('personnel_id', $ids)
                ->where('verification_status', 'verified')
                ->select('personnel_id');
            $union = $union === null ? $part : $union->unionAll($part);
        }

        if ($union === null) {
            return [];
        }

        return DB::query()
            ->fromSub($union, 'portfolio_records')
            ->groupBy('personnel_id')
            ->selectRaw('personnel_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'personnel_id')
            ->mapWithKeys(fn (mixed $count, int|string $personnelId): array => [(int) $personnelId => (float) $count])
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $joinDates
     * @return array<int, float>
     */
    private function tenure(array $joinDates): array
    {
        $today = CarbonImmutable::today();
        $tenure = [];

        foreach ($joinDates as $id => $date) {
            if (! filled($date)) {
                continue;
            }

            $joined = CarbonImmutable::parse((string) $date)->startOfDay();
            $tenure[(int) $id] = $joined->lte($today) ? round($joined->diffInDays($today) / 365.25, 1) : 0.0;
        }

        return $tenure;
    }
}
