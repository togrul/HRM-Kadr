<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\User;
use App\Services\StructureService;
use Illuminate\Validation\ValidationException;

/**
 * Saxlanan hesabat seçimi (preset, planlı hesabat): standart hesabat və ya dinamik mənbə +
 * qruplaşdırma + metrik, dövr və struktur. Ağ siyahı ilə təmizlənir və istifadəçinin hesabat
 * qrupu icazəsinə və struktur görünürlüyünə qarşı yoxlanılır — saxlananda da, tətbiq olunanda da.
 */
class ReportSelectionService
{
    public const TAB_STANDARD = 'standard';

    public const TAB_DYNAMIC = 'dynamic';

    /**
     * @var list<string>
     */
    public const TABS = [self::TAB_STANDARD, self::TAB_DYNAMIC];

    public function __construct(
        private ReportsAccessService $access,
        private StandardReportCatalogService $catalog,
        private DynamicReportBuilderService $builder,
        private StructureService $structures,
    ) {}

    /**
     * Saxlamaq üçün: hər şey etibarlı olmalıdır, əks halda ValidationException.
     *
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     *
     * @throws ValidationException
     */
    public function validated(User $user, string $tab, array $input, bool $withPeriod = true): array
    {
        $selection = $this->selection($user, $tab, $input);

        if ($withPeriod) {
            $year = (int) ($input['year'] ?? 0);
            $month = (int) ($input['month'] ?? 0);

            if ($year < 2000 || $year > 2100) {
                throw ValidationException::withMessages(['year' => __('reports::presets.errors.invalid_period')]);
            }

            if ($month < 1 || $month > 12) {
                throw ValidationException::withMessages(['month' => __('reports::presets.errors.invalid_period')]);
            }

            $selection['year'] = $year;
            $selection['month'] = $month;
        }

        $structureId = $this->structureId($input);
        if ($structureId !== null && ! $this->structures->scopeFor($user)->allows($structureId)) {
            throw ValidationException::withMessages(['structure_id' => __('reports::presets.errors.structure_out_of_scope')]);
        }

        $selection['structure_id'] = $structureId;

        return $selection;
    }

    /**
     * Tətbiq etmək üçün: hesabat qrupu icazəsi yoxdursa ValidationException; strukturu
     * istifadəçinin görünürlüyündən kənardadırsa struktur filtri atılır (hesabat istifadəçinin
     * öz görünürlüyü ilə qurulur) və `structure_dropped` qaytarılır.
     *
     * @param  array<string,mixed>  $filters
     * @return array{filters:array<string,mixed>,structure_dropped:bool}
     *
     * @throws ValidationException
     */
    public function forViewer(User $user, string $tab, array $filters): array
    {
        $selection = $this->selection($user, $tab, $filters);

        $year = (int) ($filters['year'] ?? 0);
        $month = (int) ($filters['month'] ?? 0);
        if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
            $selection['year'] = $year;
            $selection['month'] = $month;
        }

        $structureId = $this->structureId($filters);
        $dropped = $structureId !== null && ! $this->structures->scopeFor($user)->allows($structureId);
        $selection['structure_id'] = $dropped ? null : $structureId;

        return ['filters' => $selection, 'structure_dropped' => $dropped];
    }

    /**
     * Seçimin hesabat qrupu (icazə yoxlaması və siyahı süzgəci üçün).
     *
     * @param  array<string,mixed>  $filters
     */
    public function isViewable(User $user, string $tab, array $filters): bool
    {
        return match ($tab) {
            self::TAB_STANDARD => $this->access->canViewStandardReport((string) ($filters['report'] ?? ''), $user),
            self::TAB_DYNAMIC => $this->access->canViewDynamicSource((string) ($filters['source'] ?? ''), $user),
            default => false,
        };
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     *
     * @throws ValidationException
     */
    private function selection(User $user, string $tab, array $input): array
    {
        if ($tab === self::TAB_STANDARD) {
            $report = (string) ($input['report'] ?? '');
            $keys = array_column($this->catalog->all(), 'key');

            if (! in_array($report, $keys, true) || ! $this->access->canViewStandardReport($report, $user)) {
                throw ValidationException::withMessages(['report' => __('reports::presets.errors.report_not_allowed')]);
            }

            return ['report' => $report];
        }

        if ($tab === self::TAB_DYNAMIC) {
            $source = (string) ($input['source'] ?? '');
            $groupBy = (string) ($input['group_by'] ?? '');
            $metric = (string) ($input['metric'] ?? '');
            $sources = array_column($this->builder->sourceOptions(), 'key');

            if (! in_array($source, $sources, true) || ! $this->access->canViewDynamicSource($source, $user)) {
                throw ValidationException::withMessages(['source' => __('reports::presets.errors.report_not_allowed')]);
            }

            if (! in_array($groupBy, array_column($this->builder->groupOptions($source), 'key'), true)
                || ! in_array($metric, array_column($this->builder->metricOptions($source), 'key'), true)) {
                throw ValidationException::withMessages(['source' => __('reports::presets.errors.invalid_selection')]);
            }

            return ['source' => $source, 'group_by' => $groupBy, 'metric' => $metric];
        }

        throw ValidationException::withMessages(['tab' => __('reports::presets.errors.invalid_selection')]);
    }

    /**
     * @param  array<string,mixed>  $input
     */
    private function structureId(array $input): ?int
    {
        $value = $input['structure_id'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
