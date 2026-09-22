<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceScorecardItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bulk actuals from Excel/CSV (spec §8): HR downloads a sheet with one row per KPI of
 * every active card in the cycle, fills the "value" column and uploads it back. The
 * file is checked in full first; one bad row imports nothing and every problem comes
 * back with its row number.
 */
class KpiActualsImportService
{
    /** Column order of the sheet; the importer reads by position. */
    public const COLUMNS = ['item_id', 'tabel_no', 'personnel', 'kpi_code', 'kpi', 'unit', 'target', 'actual', 'value', 'note'];

    public function __construct(private readonly ScorecardService $scorecards) {}

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function columns(): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => __('performance_evaluation::kpi.import.columns.'.$key),
        ], self::COLUMNS);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function templateRows(PerformanceCycle $cycle): array
    {
        return $this->openItems($cycle)
            ->map(fn (PerformanceScorecardItem $item): array => [
                'item_id' => $item->id,
                'tabel_no' => $item->scorecard->personnel?->tabel_no,
                'personnel' => trim(($item->scorecard->personnel?->surname ?? '').' '.($item->scorecard->personnel?->name ?? '')),
                'kpi_code' => $item->kpi->code,
                'kpi' => $item->kpi->name,
                'unit' => __('performance_evaluation::kpi.units.'.$item->kpi->unit),
                'target' => $item->target === null ? null : (float) $item->target,
                'actual' => $item->actual === null ? null : (float) $item->actual,
                'value' => null,
                'note' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows  raw sheet rows, heading row included
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(PerformanceCycle $cycle, array $rows, User $user): array
    {
        $items = $this->openItems($cycle)->keyBy('id');
        $valueColumn = array_search('value', self::COLUMNS, true);
        $noteColumn = array_search('note', self::COLUMNS, true);
        $accepted = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $itemId = $row[0] ?? null;
            $value = $row[$valueColumn] ?? null;

            if (! is_numeric($itemId) || $value === null || $value === '') {
                continue; // heading row or a KPI left blank
            }

            $item = $items->get((int) $itemId);
            $error = match (true) {
                $item === null => __('performance_evaluation::kpi.import.errors.unknown_row'),
                ! is_numeric($value) => __('performance_evaluation::kpi.import.errors.not_a_number'),
                (bool) $item->kpi->evidence_required => __('performance_evaluation::kpi.import.errors.evidence_required'),
                default => null,
            };

            if ($error !== null) {
                $errors[$line] = $error;

                continue;
            }

            $note = trim((string) ($row[$noteColumn] ?? ''));
            $accepted[] = [$item, (float) $value, $note === '' ? null : $note];
        }

        if ($errors !== [] || $accepted === []) {
            return ['imported' => 0, 'errors' => $errors];
        }

        DB::transaction(function () use ($accepted, $user): void {
            foreach ($accepted as [$item, $value, $note]) {
                $this->scorecards->recordActual($item, $value, $user, null, $note, 'import');
            }
        });

        return ['imported' => count($accepted), 'errors' => []];
    }

    /**
     * @return Collection<int, PerformanceScorecardItem>
     */
    private function openItems(PerformanceCycle $cycle): Collection
    {
        return PerformanceScorecardItem::query()
            ->whereHas('scorecard', fn ($query) => $query->where('performance_cycle_id', $cycle->id)->where('status', 'active'))
            ->with(['kpi:id,code,name,unit,evidence_required', 'scorecard', 'scorecard.personnel:id,surname,name,tabel_no'])
            ->orderBy('performance_scorecard_id')
            ->orderBy('sort_order')
            ->get();
    }
}
