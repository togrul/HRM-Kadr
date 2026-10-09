<?php

namespace App\Modules\Personnel\Services;

use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\PersonnelPresenceResolver;
use App\Services\StructurePathService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PersonnelRowViewModelService
{
    public function decoratePaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $this->decorateCollection($paginator->getCollection())
        );

        return $paginator;
    }

    /**
     * @param  Collection<int, Personnel>  $collection
     * @return Collection<int, Personnel>
     */
    public function decorateCollection(Collection $collection): Collection
    {
        $activeShiftAssignments = $this->resolveActiveShiftAssignments($collection);
        $presences = app(PersonnelPresenceResolver::class)->resolveMany($collection);

        return $collection->map(function (Personnel $personnel, int $index) use ($activeShiftAssignments, $presences) {
            $presence = $presences[(int) $personnel->getKey()] ?? null;
            $activeShiftAssignment = $activeShiftAssignments->get((string) $personnel->tabel_no);

            $structurePath = app(StructurePathService::class);
            $personnel->setAttribute('structure_path', implode(' / ', $structurePath->segments($personnel->structure_id)));
            $personnel->setAttribute('structure_name', $structurePath->current($personnel->structure_id));
            $personnel->setAttribute('join_work_date_fmt', $this->formatDate($personnel->join_work_date));
            $personnel->setAttribute('leave_work_date_fmt', $this->formatDate($personnel->leave_work_date));
            $personnel->setAttribute('deleted_at_fmt', $this->formatDateTime($personnel->deleted_at));
            $personnel->setAttribute('gender_label', (int) $personnel->gender === 1 ? __('personnel::common.labels.man') : __('personnel::common.labels.woman'));
            $personnel->setAttribute('rank_label', (string) optional($personnel->latestRank?->rank)->name);
            $personnel->setAttribute('presence', $presence);
            $personnel->setAttribute('presence_status', $presence?->status->value);
            $personnel->setAttribute('presence_tone', $presence?->tone());
            $personnel->setAttribute('presence_label', $presence?->label());
            $personnel->setAttribute('presence_reason', $presence?->reason);
            $personnel->setAttribute('presence_period', $presence?->periodLabel());
            $personnel->setAttribute('presence_return', $presence?->expectedReturnLabel());
            $personnel->setAttribute('photo_url', $this->photoUrl($personnel->photo));
            $personnel->setAttribute('deleted_by_name', (string) optional($personnel->personDidDelete)->name);
            $personnel->setAttribute('active_shift_name', (string) optional($activeShiftAssignment?->shift)->name);
            $personnel->setAttribute(
                'active_shift_window',
                $activeShiftAssignment?->shift
                    ? sprintf('%s - %s', $activeShiftAssignment->shift->start_time, $activeShiftAssignment->shift->end_time)
                    : null
            );

            return $personnel;
        });
    }

    /**
     * @param  Collection<int, Personnel>  $collection
     * @return Collection<string, AttendanceShiftAssignment>
     */
    protected function resolveActiveShiftAssignments(Collection $collection): Collection
    {
        $tabelNos = $collection
            ->pluck('tabel_no')
            ->filter()
            ->unique()
            ->values();

        if ($tabelNos->isEmpty()) {
            return collect();
        }

        return AttendanceShiftAssignment::query()
            ->with('shift:id,name,start_time,end_time')
            ->whereIn('tabel_no', $tabelNos->all())
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get()
            ->unique('tabel_no')
            ->keyBy('tabel_no');
    }

    protected function photoUrl(?string $path): string
    {
        if (! empty($path)) {
            return Storage::url($path);
        }

        return asset('assets/images/no-image.png');
    }

    protected function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('d.m.Y');
    }

    protected function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('d.m.Y H:i');
    }
}
