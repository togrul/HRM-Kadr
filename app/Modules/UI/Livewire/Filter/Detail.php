<?php

namespace App\Modules\UI\Livewire\Filter;

use Livewire\Component;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use App\Livewire\Traits\DropdownConstructTrait;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

use App\Models\{
    Structure, Position, Country, City, Rank, EducationalInstitution,
    EducationDegree, Award, Punishment
};

class Detail extends Component
{
    use DropdownConstructTrait;
    // --- State ---
    public array $filter = [];

    // Search terms (debounce/lazy in Blade)
    public string $searchStructure = '';
    public string $searchPosition = '';
    public string $searchNationality = '';
    public string $searchPreviousNationality = '';
    public string $searchCity = '';
    public string $searchRank = '';
    public string $searchInstitution = '';
    public string $searchEducationDegree = '';
    public string $searchAward = '';
    public string $searchPunishment = '';


    #[On('filterResetted')]
    public function filterResetted(): void
    {
        $this->filter = $this->defaultFilter();;
    }

    #[On('setOpenFilter')]
    public function setOpenFilter(): void
    {
        $this->dispatch('openFilterWasSet');
    }

    // No explicit placeholder here—lazy instances render the Blade placeholder passed from the parent.

    private function defaultFilter(): array
    {
        return [
            'structure_id' => null,
            'position_id'  => null,
            'nationality_id' => null,
            'born_country_id' => null,
            'born_city_id' => null,
            'rank_id' => null,
            'educational_institution_id' => null,
            'education_degree_id' => null,
            'award_id' => null,
            'punishment_id' => null,
            'is_married' => null,
        ];
    }

    public function updatedFilter($value, $name): void
    {
        if ($name !== 'is_married' && ($value === '' || $value === null)) {
            unset($this->filter[$name]);
        }

        if ($name === 'born_country_id') {
            $this->filter['born_city_id'] = null;
        }
    }

    public function search()
    {
        //datani gondermek birbasa query stringe
        $this->dispatch('filterSelected', $this->filter);
    }

    #[Computed()]
    public function structureOptions(): array
    {
        $selected = data_get($this->filter, 'structure_id');

        $base = \App\Models\Structure::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchStructure,
            selectedId: $selected,
            limit: 80
        );
    }

    #[Computed]
    public function positions(): array
    {
        $selected = data_get($this->filter, 'position_id');

        $base = \App\Models\Position::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchPosition,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function rankOptions(): array
    {
        $selected = data_get($this->filter, 'rank_id');
        $localeCol = 'name_'.app()->getLocale(); // real column
        $base = \App\Models\Rank::query()
            ->select('id', DB::raw("$localeCol as label"))
            ->where('is_active', 1);

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $localeCol,
            searchTerm: $this->searchRank,
            selectedId: $selected,
            limit: 50
        );
    }

   #[Computed]
    public function institutionOptions(): array
    {
        $selected = data_get($this->filter, 'educational_institution_id');

        $base = \App\Models\EducationalInstitution::query()
            ->select('id', DB::raw("name as label"));

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchInstitution,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function educationDegreeOptions(): array
    {
        $selected  = data_get($this->filter, 'education_degree_id');
        $localeCol = 'title_'.app()->getLocale();

        $base = \App\Models\EducationDegree::query()
            ->select('id', DB::raw("$localeCol as label"));

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $localeCol,
            searchTerm: $this->searchEducationDegree,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function awardOptions(): array
    {
        $selected = data_get($this->filter, 'award_id');

        $base = \App\Models\Award::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchAward,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function punishmentOptions(): array
    {
        $selected = data_get($this->filter, 'punishment_id');

        $base = \App\Models\Punishment::query()
            ->select('id', DB::raw("name as label"))
            ->criminalType('other') // local scope
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchPunishment,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function cities(): array
    {
        $countryId = data_get($this->filter, 'born_country_id');
        if (!$countryId) return [];

        $selectedId = data_get($this->filter, 'born_city_id');

        $base = \App\Models\City::query()
            ->select('id', DB::raw('name as label'))
            ->where('country_id', $countryId)
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->searchCity,
            selectedId: $selectedId,
            limit: 50
        );
    }

    private function getBaseQueryCountry()
    {
        $locale = app()->getLocale();
        return \App\Models\Country::query()
            ->select('countries.id', DB::raw('t.title as label'))
            ->join('country_translations as t', function ($join) use ($locale) {
                $join->on('t.country_id', '=', 'countries.id')
                    ->where('t.locale', '=', $locale);
            })
            ->orderBy('t.title');
    }

    #[Computed]
    public function nationalityOptions(): array
    {
        $selected = data_get($this->filter, 'nationality_id');

        $base = $this->getBaseQueryCountry();

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 't.title',
            searchTerm: $this->searchNationality,
            selectedId: $selected,
            limit: 80
        );
    }

    #[Computed]
    public function bornCountryOptions(): array
    {
        $selected = data_get($this->filter, 'born_country_id');

        $base = $this->getBaseQueryCountry();

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 't.title',
            searchTerm: $this->searchPreviousNationality,
            selectedId: $selected,
            limit: 80
        );
    }

    public function mount(array $filter = []): void
    {
        // Merge incoming filter (from parent/query) with defaults so selections stay visible.
        $this->filter = array_merge($this->defaultFilter(), array_filter($filter, fn ($v) => $v !== null && $v !== ''));
    }

    public function render() { return view('ui::livewire.filter.detail'); }
}
