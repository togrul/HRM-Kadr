<?php

namespace App\Modules\Candidates\Support\Traits;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Candidate;
use App\Models\CandidateSource;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait BuildsRecruitmentOptions
{
    use DropdownConstructTrait;

    protected function recruitmentStructureOptions(?int $selectedId, string $searchProperty = 'searchStructure'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:structures', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'name', $search, $selectedId, 50);
    }

    protected function recruitmentPositionOptions(?int $selectedId, string $searchProperty = 'searchPosition'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = Position::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:positions', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'name', $search, $selectedId, 50);
    }

    protected function recruitmentOwnerOptions(?int $selectedId, string $searchProperty = 'searchOwner'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = User::query()
            ->select('id', DB::raw("CONCAT(name, ' <', email, '>') as label"))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:owners', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'name', $search, $selectedId, 50);
    }

    protected function recruitmentRequisitionOptions(?int $selectedId, string $searchProperty = 'searchRequisition'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = JobRequisition::query()
            ->select('id', DB::raw('title as label'))
            ->orderByDesc('id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:requisitions', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'title', $search, $selectedId, 50);
    }

    protected function recruitmentOpeningOptions(?int $selectedId, string $searchProperty = 'searchOpening'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = JobOpening::query()
            ->select('id', DB::raw('title as label'))
            ->orderByDesc('id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:openings', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'title', $search, $selectedId, 50);
    }

    protected function recruitmentCandidateOptions(?int $selectedId, string $searchProperty = 'searchCandidate'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = Candidate::query()
            ->select('id', DB::raw("CONCAT(surname, ' ', name, ' ', patronymic) as label"))
            ->orderByDesc('id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:candidates', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'surname', $search, $selectedId, 50, function ($query) use ($search) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('surname', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('patronymic', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        });
    }

    protected function recruitmentSourceOptions(?int $selectedId, string $searchProperty = 'searchSource'): array
    {
        $search = $this->dropdownSearch($searchProperty);
        $base = CandidateSource::query()
            ->select('id', DB::raw('name as label'))
            ->where('is_active', true)
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected('candidates:recruitment:sources', $base, $selectedId, 80);
        }

        return $this->optionsWithSelected($base, 'name', $search, $selectedId, 50);
    }
}
