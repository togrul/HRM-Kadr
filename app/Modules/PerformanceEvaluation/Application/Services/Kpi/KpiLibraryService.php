<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceKpi;
use App\Models\PerformanceKpiTemplateItem;
use App\Models\PerformanceScorecardItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The KPI library (spec §4.1): every change to a scoring field (type, direction, unit,
 * aggregation, scale) cuts a new version, so scorecards already opened keep scoring
 * against the version they were built from. A KPI in use is archived, never deleted.
 */
class KpiLibraryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?PerformanceKpi $kpi = null): PerformanceKpi
    {
        return DB::transaction(function () use ($data, $kpi): PerformanceKpi {
            if ($kpi === null) {
                $kpi = PerformanceKpi::query()->create([...$data, 'current_version' => 1, 'created_by' => auth()->id()]);
                $this->cutVersion($kpi);

                return $kpi;
            }

            $kpi->fill($data);
            $versionChanged = $kpi->isDirty(PerformanceKpi::VERSIONED_FIELDS);
            if ($versionChanged) {
                $kpi->current_version++;
            }
            $kpi->save();

            if ($versionChanged) {
                $this->cutVersion($kpi);
            }

            return $kpi;
        });
    }

    public function archive(PerformanceKpi $kpi): void
    {
        $kpi->update(['status' => 'archived']);
    }

    /**
     * @throws ValidationException when the KPI is used by a template or a scorecard
     */
    public function delete(PerformanceKpi $kpi): void
    {
        $inUse = PerformanceKpiTemplateItem::query()->where('performance_kpi_id', $kpi->id)->exists()
            || PerformanceScorecardItem::query()->where('performance_kpi_id', $kpi->id)->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'kpi' => __('performance_evaluation::kpi.errors.kpi_in_use'),
            ]);
        }

        $kpi->delete();
    }

    /**
     * The scoring definition a scorecard item is evaluated with.
     *
     * @return array<string, mixed>
     */
    public static function scoringSnapshot(PerformanceKpi $kpi): array
    {
        return [
            'code' => $kpi->code,
            'name' => $kpi->name,
            ...$kpi->only(PerformanceKpi::VERSIONED_FIELDS),
        ];
    }

    private function cutVersion(PerformanceKpi $kpi): void
    {
        $kpi->versions()->create([
            'version' => $kpi->current_version,
            'snapshot' => self::scoringSnapshot($kpi),
            'effective_from' => now(),
            'created_by' => auth()->id(),
        ]);
    }
}
