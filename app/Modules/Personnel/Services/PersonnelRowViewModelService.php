<?php

namespace App\Modules\Personnel\Services;

use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PersonnelRowViewModelService
{
    /** @var array<int, string> */
    protected array $structurePathCache = [];

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

        return $collection->map(function (Personnel $personnel, int $index) use ($activeShiftAssignments) {
            $vacation = $personnel->activeVacation;
            $businessTrip = $personnel->activeBusinessTrip;
            $activeShiftAssignment = $activeShiftAssignments->get((string) $personnel->tabel_no);

            $personnel->setAttribute('structure_path', $this->resolveStructurePath($personnel->structure));
            $personnel->setAttribute('join_work_date_fmt', $this->formatDate($personnel->join_work_date));
            $personnel->setAttribute('leave_work_date_fmt', $this->formatDate($personnel->leave_work_date));
            $personnel->setAttribute('deleted_at_fmt', $this->formatDateTime($personnel->deleted_at));
            $personnel->setAttribute('gender_label', (int) $personnel->gender === 1 ? __('Man') : __('Woman'));
            $personnel->setAttribute('rank_label', (string) optional($personnel->latestRank?->rank)->name);
            $personnel->setAttribute('active_vacation', $vacation);
            $personnel->setAttribute('active_business_trip', $businessTrip);
            $personnel->setAttribute('active_vacation_start', $this->formatDate($vacation?->start_date));
            $personnel->setAttribute('active_vacation_end', $this->formatDate($vacation?->return_work_date));
            $personnel->setAttribute('active_business_trip_start', $this->formatDate($businessTrip?->start_date));
            $personnel->setAttribute('active_business_trip_end', $this->formatDate($businessTrip?->end_date));
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

    protected function resolveStructurePath(?Structure $structure): string
    {
        if (! $structure) {
            return '';
        }

        $cacheKey = (int) $structure->id;

        if (isset($this->structurePathCache[$cacheKey])) {
            return $this->structurePathCache[$cacheKey];
        }

        $segments = [];
        $cursor = $structure;


        while ($cursor) {
          $segments[] = (string) $cursor->name;
  
          if (! $cursor->relationLoaded('parent')) {
              break;
          }
  
          $parent = $cursor->parent;
  
          // parent yoksa veya parent kurumsal root ise stop (kurum adını alma)
          if (! $parent || is_null($parent->parent_id)) {
              break;
          }
  
          $cursor = $parent;
      }

      return $this->structurePathCache[$cacheKey] = implode(' ', array_reverse($segments));
    }
}
