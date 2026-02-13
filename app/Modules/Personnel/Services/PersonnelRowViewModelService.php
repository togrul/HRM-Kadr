<?php

namespace App\Modules\Personnel\Services;

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
        return $collection->map(function (Personnel $personnel) {
            $personnel->setAttribute('structure_path', $this->resolveStructurePath($personnel->structure));
            $personnel->setAttribute('join_work_date_fmt', $this->formatDate($personnel->join_work_date));
            $personnel->setAttribute('leave_work_date_fmt', $this->formatDate($personnel->leave_work_date));
            $personnel->setAttribute('deleted_at_fmt', $this->formatDateTime($personnel->deleted_at));
            $personnel->setAttribute('gender_label', (int) $personnel->gender === 1 ? __('Man') : __('Woman'));
            $personnel->setAttribute('rank_label', (string) optional($personnel->latestRank?->rank)->name);
            $personnel->setAttribute('active_vacation', $personnel->activeVacation);
            $personnel->setAttribute('active_business_trip', $personnel->activeBusinessTrip);
            $personnel->setAttribute('photo_url', $this->photoUrl($personnel->photo));
            $personnel->setAttribute('deleted_by_name', (string) optional($personnel->personDidDelete)->name);

            return $personnel;
        });
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
