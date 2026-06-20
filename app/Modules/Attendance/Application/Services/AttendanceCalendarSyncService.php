<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\Personnel;
use App\Models\Structure;
use Carbon\Carbon;

class AttendanceCalendarSyncService
{
    /**
     * @param  array<string,mixed>  $original
     */
    public function syncCalendarChange(?AttendanceCalendar $calendar = null, array $original = []): void
    {
        $targets = collect()
            ->merge($this->resolveTargetsFromCurrent($calendar))
            ->merge($this->resolveTargetsFromOriginal($original))
            ->filter(fn ($target) => is_array($target) && ! empty($target['date']) && ! empty($target['scope_type']))
            ->unique(fn (array $target) => implode('|', [
                $target['date'],
                $target['scope_type'],
                (string) ($target['scope_id'] ?? ''),
            ]))
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        $pipeline = app(AttendancePunchProcessingPipelineService::class);

        foreach ($targets as $target) {
            $date = Carbon::parse((string) $target['date'])->startOfDay();
            $tabelNos = $this->resolveScopedTabelNos(
                date: $date,
                scopeType: (string) $target['scope_type'],
                scopeId: is_numeric($target['scope_id'] ?? null) ? (int) $target['scope_id'] : null
            );

            if ($tabelNos === []) {
                continue;
            }

            $pipeline->process(
                from: $date->copy()->startOfDay(),
                to: $date->copy()->endOfDay(),
                source: null,
                options: [
                    'include_processed' => true,
                    'mark_processed' => false,
                    'structure_id' => null,
                    'tabel_nos' => $tabelNos,
                    'force_dates' => [$date->toDateString()],
                ]
            );
        }
    }

    /**
     * @return array<int,array{date:string,scope_type:string,scope_id:int|null}>
     */
    private function resolveTargetsFromCurrent(?AttendanceCalendar $calendar): array
    {
        if (! $calendar instanceof AttendanceCalendar) {
            return [];
        }

        if (! $calendar->date || ! filled($calendar->scope_type)) {
            return [];
        }

        return [[
            'date' => $calendar->date->toDateString(),
            'scope_type' => (string) $calendar->scope_type,
            'scope_id' => $calendar->scope_id ? (int) $calendar->scope_id : null,
        ]];
    }

    /**
     * @param  array<string,mixed>  $original
     * @return array<int,array{date:string,scope_type:string,scope_id:int|null}>
     */
    private function resolveTargetsFromOriginal(array $original): array
    {
        if (! filled($original['date'] ?? null) || ! filled($original['scope_type'] ?? null)) {
            return [];
        }

        return [[
            'date' => Carbon::parse((string) $original['date'])->toDateString(),
            'scope_type' => (string) $original['scope_type'],
            'scope_id' => is_numeric($original['scope_id'] ?? null) ? (int) $original['scope_id'] : null,
        ]];
    }

    /**
     * @return array<int,string>
     */
    private function resolveScopedTabelNos(Carbon $date, string $scopeType, ?int $scopeId): array
    {
        return Personnel::query()
            ->where('is_pending', 0)
            ->whereDate('join_work_date', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('leave_work_date')
                    ->orWhereDate('leave_work_date', '>=', $date->toDateString());
            })
            ->when(
                $scopeType === 'structure' && $scopeId !== null,
                fn ($query) => $query->whereIn('structure_id', $this->resolveStructureScopeIds($scopeId))
            )
            ->pluck('tabel_no')
            ->filter()
            ->unique()
            ->values()
            ->all();
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

        while ($stack !== []) {
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
