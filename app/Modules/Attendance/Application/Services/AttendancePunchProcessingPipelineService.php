<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceManualEntry;
use App\Models\AttendanceOvertimeRequest;
use App\Models\AttendanceRawPunch;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendancePunchProcessingPipelineService
{
    /**
     * @param  array{
     *   include_processed?:bool,
     *   mark_processed?:bool,
     *   structure_id?:int|null,
     *   tabel_nos?:array<int,string>|null
     * }  $options
     * @return array<string,int>
     */
    public function process(Carbon $from, Carbon $to, ?string $source = null, array $options = []): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $scanFrom = $from->copy()->subDay();
        $scanTo = $to->copy()->addDay()->endOfDay();

        $opts = array_merge([
            'include_processed' => false,
            'mark_processed' => true,
            'structure_id' => null,
            'tabel_nos' => null,
        ], $options);

        $scopeTabelNos = $this->resolveScopeTabelNos(
            $opts['tabel_nos'],
            is_numeric($opts['structure_id']) ? (int) $opts['structure_id'] : null
        );

        $normalizationStats = app(AttendancePunchNormalizationService::class)->normalize(
            from: $scanFrom,
            to: $scanTo,
            source: $source,
            tabelNos: $scopeTabelNos
        );

        $allPunches = AttendanceRawPunch::query()
            ->whereBetween('punched_at', [$scanFrom, $scanTo])
            ->when(
                ! (bool) $opts['include_processed'],
                fn ($query) => $query->where('is_processed', false)
            )
            ->when($source, fn ($query) => $query->where('source', $source))
            ->when(
                is_array($scopeTabelNos) && ! empty($scopeTabelNos),
                fn ($query) => $query->whereIn('tabel_no', $scopeTabelNos)
            )
            ->orderBy('tabel_no')
            ->orderBy('punched_at')
            ->get();

        $manualEntries = AttendanceManualEntry::query()
            ->with([
                'calculationShift:id,name,start_time,end_time,break_minutes,is_night_shift,in_flex_before_minutes,in_flex_after_minutes,out_flex_before_minutes,out_flex_after_minutes',
            ])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->where('approval_status', 'approved')
            ->when(
                is_array($scopeTabelNos) && ! empty($scopeTabelNos),
                fn ($query) => $query->whereIn('tabel_no', $scopeTabelNos)
            )
            ->get();

        $manualMap = $manualEntries->keyBy(
            fn (AttendanceManualEntry $entry) => $entry->tabel_no.'|'.$entry->date->toDateString()
        );

        $baseTabelNos = collect()
            ->merge($allPunches->pluck('tabel_no'))
            ->merge($manualEntries->pluck('tabel_no'))
            ->when(
                is_array($scopeTabelNos) && ! empty($scopeTabelNos),
                fn (Collection $collection) => $collection->merge($scopeTabelNos)
            )
            ->filter()
            ->unique()
            ->values();

        if ($baseTabelNos->isEmpty()) {
            return [
                'normalized' => (int) $normalizationStats['normalized'],
                'processed_punches' => 0,
                'ledger_upserts' => 0,
                'exception_sync_rows' => 0,
                'locked_skipped_days' => 0,
            ];
        }

        $structureByTabel = Personnel::query()
            ->whereIn('tabel_no', $baseTabelNos)
            ->pluck('structure_id', 'tabel_no')
            ->map(fn ($v) => $v !== null ? (int) $v : null)
            ->all();

        $contextResolver = app(AttendanceDayContextResolverService::class);
        $context = $contextResolver->build(
            from: $from,
            to: $to,
            tabelNos: $baseTabelNos,
            structureByTabel: $structureByTabel
        );

        $overtimeApprovedMap = AttendanceOvertimeRequest::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->where('status', 'approved')
            ->when(
                is_array($scopeTabelNos) && ! empty($scopeTabelNos),
                fn ($query) => $query->whereIn('tabel_no', $scopeTabelNos)
            )
            ->get(['tabel_no', 'date', 'approved_minutes'])
            ->mapWithKeys(fn (AttendanceOvertimeRequest $request) => [
                $request->tabel_no.'|'.$request->date->toDateString() => (int) $request->approved_minutes,
            ])
            ->all();

        $assignments = AttendanceShiftAssignment::query()
            ->with(['shift:id,name,start_time,end_time,break_minutes,is_night_shift,in_flex_before_minutes,in_flex_after_minutes,out_flex_before_minutes,out_flex_after_minutes'])
            ->whereIn('tabel_no', $baseTabelNos)
            ->where('is_active', true)
            ->where('effective_from', '<=', $to->toDateString())
            ->where(function ($query) use ($from): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $from->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('tabel_no');

        $settings = AttendanceSetting::query()
            ->where('is_active', true)
            ->where('scope_type', 'global')
            ->latest('id')
            ->first();

        $defaultShift = null;
        if ($settings?->default_shift_id) {
            $defaultShift = AttendanceShift::query()->find($settings->default_shift_id);
        }

        $pairingService = app(AttendancePunchPairingService::class);
        $calculator = app(AttendanceDailyLedgerCalculatorService::class);
        $exceptionService = app(AttendanceExceptionService::class);
        $monthLockService = app(AttendanceMonthLockService::class);
        $shiftWindowService = app(AttendanceShiftWindowService::class);

        $punchesByTabel = $allPunches->groupBy('tabel_no');
        $existingLedgerDatesByTabel = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('tabel_no', $baseTabelNos)
            ->get(['tabel_no', 'date'])
            ->groupBy('tabel_no')
            ->map(fn (Collection $rows) => $rows->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all())
            ->all();

        $dateKeysByTabel = [];
        foreach ($baseTabelNos as $tabelNo) {
            $tabelNo = (string) $tabelNo;

            $fromPunches = collect($punchesByTabel->get($tabelNo, collect()))
                ->map(fn (AttendanceRawPunch $punch) => $punch->punched_at?->toDateString())
                ->filter()
                ->all();

            $fromManual = $manualEntries
                ->where('tabel_no', $tabelNo)
                ->map(fn (AttendanceManualEntry $entry) => $entry->date?->toDateString())
                ->filter()
                ->all();

            $fromOverrides = $context['override_keys_by_tabel'][$tabelNo] ?? [];
            $fromLedger = $existingLedgerDatesByTabel[$tabelNo] ?? [];

            $dateKeysByTabel[$tabelNo] = collect()
                ->merge($fromPunches)
                ->merge($fromManual)
                ->merge($fromOverrides)
                ->merge($fromLedger)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $ledgerUpserts = 0;
        $processedPunchIds = [];
        $usedPunchIds = [];
        $syncedExceptionRows = 0;
        $lockedSkipped = 0;

        DB::transaction(function () use (
            $dateKeysByTabel,
            $punchesByTabel,
            $manualMap,
            $assignments,
            $settings,
            $defaultShift,
            $contextResolver,
            $context,
            $overtimeApprovedMap,
            $pairingService,
            $calculator,
            $exceptionService,
            $monthLockService,
            $shiftWindowService,
            $opts,
            $structureByTabel,
            &$ledgerUpserts,
            &$processedPunchIds,
            &$usedPunchIds,
            &$syncedExceptionRows,
            &$lockedSkipped
        ): void {
            foreach ($dateKeysByTabel as $tabelNo => $dateKeys) {
                if (empty($dateKeys)) {
                    continue;
                }

                $tabelPunches = collect($punchesByTabel->get((string) $tabelNo, collect()));

                foreach ($dateKeys as $dateString) {
                    $date = Carbon::parse((string) $dateString)->startOfDay();
                    $key = (string) $tabelNo.'|'.$date->toDateString();
                    $manualEntry = $manualMap->get($key);
                    $structureId = $structureByTabel[(string) $tabelNo] ?? null;

                    $shift = $manualEntry?->calculation_shift_source === 'explicit'
                        ? $manualEntry->calculationShift
                        : $this->resolveShiftForDate(
                            assignmentsForTabel: $assignments->get((string) $tabelNo, collect()),
                            date: $date,
                            defaultShift: $defaultShift
                        );

                    $window = $shiftWindowService->resolve($date, $shift);

                    $dayPunches = $tabelPunches
                        ->filter(function (AttendanceRawPunch $punch) use ($window, $usedPunchIds): bool {
                            if (! ($punch->punched_at instanceof Carbon)) {
                                return false;
                            }

                            if (isset($usedPunchIds[(int) $punch->id])) {
                                return false;
                            }

                            return $punch->punched_at->betweenIncluded($window['window_start'], $window['window_end']);
                        })
                        ->values();

                    $pairing = $pairingService->pair($dayPunches);
                    $override = $contextResolver->resolveOverride($date, (string) $tabelNo, $context['overrides']);
                    $calendarDayType = $contextResolver->resolveCalendarDayType(
                        date: $date,
                        structureId: $structureId,
                        globalMap: $context['calendars_global'],
                        structureMap: $context['calendars_structure']
                    );

                    if ($monthLockService->isPeriodLocked($date)) {
                        $lockedSkipped++;
                        foreach (($pairing['consumed_punch_ids'] ?? []) as $id) {
                            $usedPunchIds[(int) $id] = true;
                            $processedPunchIds[] = (int) $id;
                        }
                        continue;
                    }

                    $ledgerPayload = $calculator->calculate(
                        date: $date,
                        pairing: $pairing,
                        shift: $shift,
                        manualEntry: $manualEntry,
                        setting: $settings,
                        calendarDayType: $calendarDayType,
                        override: $override,
                        approvedOvertimeMinutes: $overtimeApprovedMap[$key] ?? null
                    );

                    AttendanceDailyLedger::query()->updateOrCreate(
                        [
                            'tabel_no' => (string) $tabelNo,
                            'date' => $date->toDateString(),
                        ],
                        $ledgerPayload
                    );

                    $exceptionService->syncDayExceptions(
                        tabelNo: (string) $tabelNo,
                        date: $date,
                        pairing: $override === null ? $pairing : [
                            'unmatched' => 0,
                            'missing_in' => false,
                            'missing_out' => false,
                        ]
                    );

                    $ledgerUpserts++;
                    $syncedExceptionRows += 3;

                    foreach (($pairing['consumed_punch_ids'] ?? []) as $id) {
                        $usedPunchIds[(int) $id] = true;
                        $processedPunchIds[] = (int) $id;
                    }
                }
            }

            if (! empty($processedPunchIds) && (bool) $opts['mark_processed'] && ! (bool) $opts['include_processed']) {
                AttendanceRawPunch::query()
                    ->whereIn('id', array_values(array_unique($processedPunchIds)))
                    ->update([
                        'is_processed' => true,
                        'processed_at' => now(),
                    ]);
            }
        });

        $summaryScopes = collect($structureByTabel)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if (is_int($opts['structure_id'])) {
            $summaryScopes = [(int) $opts['structure_id']];
        }

        app(AttendanceStructureSummaryService::class)->rebuildRange(
            from: $from,
            to: $to,
            structureIds: $summaryScopes === [] ? null : $summaryScopes
        );

        app(AttendanceCacheService::class)->forgetOverviewRange(
            from: $from,
            to: $to,
            structureIds: $summaryScopes === [] ? null : $summaryScopes
        );

        return [
            'normalized' => (int) $normalizationStats['normalized'],
            'processed_punches' => count(array_unique($processedPunchIds)),
            'ledger_upserts' => $ledgerUpserts,
            'exception_sync_rows' => $syncedExceptionRows,
            'locked_skipped_days' => $lockedSkipped,
        ];
    }

    /**
     * @param  Collection<int,AttendanceShiftAssignment>  $assignmentsForTabel
     */
    private function resolveShiftForDate(
        Collection $assignmentsForTabel,
        Carbon $date,
        ?AttendanceShift $defaultShift = null
    ): ?AttendanceShift {
        /** @var AttendanceShiftAssignment $assignment */
        foreach ($assignmentsForTabel as $assignment) {
            $from = $assignment->effective_from?->copy()->startOfDay();
            $to = $assignment->effective_to?->copy()->endOfDay();

            if ($from === null) {
                continue;
            }

            if ($date->lt($from)) {
                continue;
            }

            if ($to !== null && $date->gt($to)) {
                continue;
            }

            return $assignment->shift;
        }

        return $defaultShift;
    }

    /**
     * @param  array<int,string>|null  $tabelNos
     * @return array<int,string>|null
     */
    private function resolveScopeTabelNos(?array $tabelNos, ?int $structureId): ?array
    {
        $requested = collect($tabelNos ?? [])->filter()->values();

        if ($structureId === null) {
            return $requested->isNotEmpty() ? $requested->all() : null;
        }

        $structureIds = $this->resolveStructureScopeIds($structureId);

        $fromStructure = Personnel::query()
            ->whereIn('structure_id', $structureIds)
            ->pluck('tabel_no')
            ->filter()
            ->unique()
            ->values();

        if ($requested->isEmpty()) {
            return $fromStructure->all();
        }

        return $fromStructure->intersect($requested)->values()->all();
    }

    /**
     * @return array<int,int>
     */
    private function resolveStructureScopeIds(int $rootId): array
    {
        $rows = Structure::query()->get(['id', 'parent_id']);
        $childrenByParent = [];
        foreach ($rows as $row) {
            $parentId = $row->parent_id !== null ? (int) $row->parent_id : 0;
            $childrenByParent[$parentId] ??= [];
            $childrenByParent[$parentId][] = (int) $row->id;
        }

        $result = [];
        $stack = [$rootId];
        while (! empty($stack)) {
            $id = (int) array_pop($stack);
            if (isset($result[$id])) {
                continue;
            }

            $result[$id] = $id;
            foreach ($childrenByParent[$id] ?? [] as $childId) {
                if (! isset($result[$childId])) {
                    $stack[] = $childId;
                }
            }
        }

        return array_values($result);
    }
}
