<?php

namespace App\Modules\UI\Livewire\Filter;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Livewire\Traits\DropdownConstructTrait;
use Livewire\Attributes\Computed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Detail extends Component
{
    use DropdownConstructTrait;

    // --- State ---
    public array $filter = [];
    public bool $ready = true;
    public array $loadedOptionGroups = [];
    public int $openSequence = 0;

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
        $this->filter = $this->defaultFilter();
    }

    #[On('setOpenFilter')]
    public function setOpenFilter(array $filter = []): void
    {
        $normalized = $this->normalizeIncomingFilter($filter);

        if ($this->hasAnyActiveFilter($normalized)) {
            $this->filter = $normalized;
        } elseif (empty($this->filter)) {
            $this->filter = $this->defaultFilter();
        }

        $this->openSequence++;
        $this->ready = true;
        $this->loadedOptionGroups = [];
        $this->dispatch('openFilterWasSet');
    }

    public function placeholder()
    {
        return view('ui::livewire.filter.placeholders.detail');
    }

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

    private function optionsForFilter(
        Builder $base,
        string $searchColumn,
        ?string $searchTerm,
        int|string|null $selectedId,
        string $cacheKey,
        string $optionKey,
        int $limit = 50
    ): array {
        if (! data_get($this->loadedOptionGroups, $optionKey, false)) {
            // Keep selected value visible without loading the full option list.
            return $this->appendSelectedOption([], $base, $selectedId);
        }

        $searchTerm = trim((string) $searchTerm);

        if ($searchTerm === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: $cacheKey,
                base: $base,
                selectedId: $selectedId,
                limit: $limit
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $searchColumn,
            searchTerm: $searchTerm,
            selectedId: $selectedId,
            limit: $limit
        );
    }

    public function loadOptionGroup(string $group): void
    {
        if (data_get($this->loadedOptionGroups, $group, false)) {
            return;
        }

        $this->loadedOptionGroups[$group] = true;
    }

    public function updatedFilter($value, $name): void
    {
        if ($name !== 'is_married' && ($value === '' || $value === null)) {
            unset($this->filter[$name]);
        }

        if ($name === 'born_country_id') {
            $this->filter['born_city_id'] = null;
            $this->loadedOptionGroups['city'] = false;
        }
    }

    public function search()
    {
        //datani gondermek birbasa query stringe
        $this->dispatch('filterSelected', $this->filter);
    }

    #[On('filterSelected')]
    public function pauseOptionLoading(): void
    {
        $this->ready = false;
    }

    #[Computed()]
    public function structureOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'structure_id');

        $base = \App\Models\Structure::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchStructure,
            selectedId: $selected,
            cacheKey: 'personnel:structures',
            optionKey: 'structure',
            limit: 80
        );
    }

    #[Computed]
    public function positions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'position_id');

        $base = \App\Models\Position::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchPosition,
            selectedId: $selected,
            cacheKey: 'personnel:positions',
            optionKey: 'position',
            limit: 50
        );
    }

    #[Computed]
    public function rankOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'rank_id');
        $localeCol = 'name_'.app()->getLocale(); // real column
        $base = \App\Models\Rank::query()
            ->select('id', DB::raw("$localeCol as label"))
            ->where('is_active', 1);

        return $this->optionsForFilter(
            base: $base,
            searchColumn: $localeCol,
            searchTerm: $this->searchRank,
            selectedId: $selected,
            cacheKey: "personnel:ranks:".app()->getLocale(),
            optionKey: 'rank',
            limit: 50
        );
    }

   #[Computed]
    public function institutionOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'educational_institution_id');

        $base = \App\Models\EducationalInstitution::query()
            ->select('id', DB::raw("name as label"));

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchInstitution,
            selectedId: $selected,
            cacheKey: 'personnel:educational_institutions',
            optionKey: 'institution',
            limit: 50
        );
    }

    #[Computed]
    public function educationDegreeOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected  = data_get($this->filter, 'education_degree_id');
        $localeCol = 'title_'.app()->getLocale();

        $base = \App\Models\EducationDegree::query()
            ->select('id', DB::raw("$localeCol as label"));

        return $this->optionsForFilter(
            base: $base,
            searchColumn: $localeCol,
            searchTerm: $this->searchEducationDegree,
            selectedId: $selected,
            cacheKey: "personnel:education_degrees:".app()->getLocale(),
            optionKey: 'educationDegree',
            limit: 50
        );
    }

    #[Computed]
    public function awardOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'award_id');

        $base = \App\Models\Award::query()
            ->select('id', DB::raw("name as label"))
            ->orderBy('name');

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchAward,
            selectedId: $selected,
            cacheKey: 'personnel:awards',
            optionKey: 'award',
            limit: 50
        );
    }

    #[Computed]
    public function punishmentOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'punishment_id');

        $base = \App\Models\Punishment::query()
            ->select('id', DB::raw("name as label"))
            ->criminalType('other') // local scope
            ->orderBy('name');

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchPunishment,
            selectedId: $selected,
            cacheKey: 'personnel:punishments',
            optionKey: 'punishment',
            limit: 50
        );
    }

    #[Computed]
    public function cities(): array
    {
        if (! $this->ready) {
            return [];
        }

        $countryId = data_get($this->filter, 'born_country_id');
        if (!$countryId) return [];

        $selectedId = data_get($this->filter, 'born_city_id');

        $base = \App\Models\City::query()
            ->select('id', DB::raw('name as label'))
            ->where('country_id', $countryId)
            ->orderBy('name');

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 'name',
            searchTerm: $this->searchCity,
            selectedId: $selectedId,
            cacheKey: "personnel:cities:{$countryId}",
            optionKey: 'city',
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
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'nationality_id');

        $base = $this->getBaseQueryCountry();

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 't.title',
            searchTerm: $this->searchNationality,
            selectedId: $selected,
            cacheKey: "personnel:countries:".app()->getLocale(),
            optionKey: 'nationality',
            limit: 80
        );
    }

    #[Computed]
    public function bornCountryOptions(): array
    {
        if (! $this->ready) {
            return [];
        }

        $selected = data_get($this->filter, 'born_country_id');

        $base = $this->getBaseQueryCountry();

        return $this->optionsForFilter(
            base: $base,
            searchColumn: 't.title',
            searchTerm: $this->searchPreviousNationality,
            selectedId: $selected,
            cacheKey: "personnel:countries:".app()->getLocale(),
            optionKey: 'bornCountry',
            limit: 80
        );
    }

    public function mount(array $filter = [], bool $autoOpen = false): void
    {
        $this->filter = $this->normalizeIncomingFilter($filter);
        $this->ready = true;
        $this->loadedOptionGroups = [];
        $this->dispatch('filterDetailReady');

        if ($autoOpen) {
            $this->ready = true;
            $this->dispatch('openFilterWasSet');
        }
    }

    private function normalizeIncomingFilter(array $filter): array
    {
        if (array_key_exists('filter', $filter) && is_array($filter['filter'])) {
            $filter = $filter['filter'];
        }

        return array_merge(
            $this->defaultFilter(),
            array_filter($filter, fn ($value) => $value !== null && $value !== '')
        );
    }

    private function hasAnyActiveFilter(array $filter): bool
    {
        foreach ($filter as $value) {
            if (is_array($value)) {
                if (! empty(array_filter($value, fn ($nested) => $nested !== null && $nested !== ''))) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    public function render() { return view('ui::livewire.filter.detail'); }
}
