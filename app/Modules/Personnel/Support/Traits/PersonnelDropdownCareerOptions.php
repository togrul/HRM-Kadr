<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Models\Award;
use App\Models\Disability;
use App\Models\EducationDegree;
use App\Models\EducationDocumentType;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Position;
use App\Models\Punishment;
use App\Models\Rank;
use App\Models\RankReason;
use App\Models\ScientificDegreeAndName;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\WorkNorm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait PersonnelDropdownCareerOptions
{
    #[Computed]
    public function rankOptions(): array
    {
        return $this->rankOptionsFor(
            search: $this->dropdownSearch('searchRank'),
            selectedId: $this->currentRankSelection('rank_id')
        );
    }

    #[Computed]
    public function militaryRankOptions(): array
    {
        return $this->rankOptionsFor(
            search: $this->dropdownSearch('searchMilitaryRank'),
            selectedId: $this->historyFormSelection('rank_id')
        );
    }

    #[Computed]
    public function rankReasonOptions(): array
    {
        return $this->rankReasonOptionsFor(
            search: $this->dropdownSearch('searchRankReason'),
            selectedId: $this->currentRankSelection('rank_reason_id')
        );
    }

    #[Computed]
    public function awardOptions(): array
    {
        return $this->awardOptionsFor(
            search: $this->dropdownSearch('searchAward'),
            selectedId: $this->currentAwardSelection()
        );
    }

    #[Computed]
    public function punishmentOptions(): array
    {
        return $this->punishmentOptionsFor(
            search: $this->dropdownSearch('searchPunishment'),
            selectedId: $this->currentPunishmentSelection()
        );
    }

    #[Computed]
    public function kinshipOptions(): array
    {
        return $this->kinshipOptionsFor(
            search: $this->dropdownSearch('searchKinship'),
            selectedId: $this->currentKinshipSelection()
        );
    }

    #[Computed]
    public function languageOptions(): array
    {
        return $this->languageOptionsFor(
            search: $this->dropdownSearch('searchLanguage'),
            selectedId: $this->currentLanguageSelection()
        );
    }

    #[Computed]
    public function scientificDegreeOptions(): array
    {
        return $this->scientificDegreeOptionsFor(
            search: $this->dropdownSearch('searchDegree'),
            selectedId: $this->currentDegreeSelection()
        );
    }

    #[Computed]
    public function step8DocumentTypeOptions(): array
    {
        return $this->step8DocumentTypeOptionsFor(
            search: $this->dropdownSearch('searchDegreeDocumentType'),
            selectedId: $this->currentDegreeDocumentSelection()
        );
    }

    protected function rankOptionsFor(string $search, $selectedId): array
    {
        $locale = app()->getLocale();
        $labelColumn = "name_{$locale}";

        $base = Rank::query()
            ->select('id', DB::raw("$labelColumn as label"))
            ->where('is_active', true)
            ->orderBy($labelColumn);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:ranks:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function rankReasonOptionsFor(string $search, $selectedId): array
    {
        $base = RankReason::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:rank_reasons',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function awardOptionsFor(string $search, $selectedId): array
    {
        $base = Award::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:awards',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function punishmentOptionsFor(string $search, $selectedId): array
    {
        $base = Punishment::query()
            ->select('id', DB::raw('name as label'))
            ->criminalType('other')
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:punishments',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function kinshipOptionsFor(string $search, $selectedId): array
    {
        $locale = app()->getLocale();
        $labelColumn = "name_{$locale}";

        $base = Kinship::query()
            ->select('id', DB::raw("{$labelColumn} as label"))
            ->where('is_active', 1)
            ->orderBy($labelColumn);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:kinships:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function languageOptionsFor(string $search, $selectedId): array
    {
        $base = Language::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:languages',
                base: $base,
                selectedId: $selectedId,
                limit: 100
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 100
        );
    }

    protected function scientificDegreeOptionsFor(string $search, $selectedId): array
    {
        $base = ScientificDegreeAndName::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:scientific-degrees',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function step8DocumentTypeOptionsFor(string $search, $selectedId): array
    {
        $base = EducationDocumentType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:step8:doc-types',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    #[Computed]
    public function structureOptions(): array
    {
        return $this->structureOptionsFor(
            searchKey: 'searchStructure',
            selectedId: $this->dropdownSelected('structure_id')
        );
    }

    #[Computed]
    public function laborStructureOptions(): array
    {
        return $this->structureOptionsFor(
            searchKey: 'searchLaborStructure',
            selectedId: $this->laborSelected('structure_id')
        );
    }

    #[Computed]
    public function positionOptions(): array
    {
        return $this->positionOptionsFor(
            searchKey: 'searchPosition',
            selectedId: $this->dropdownSelected('position_id')
        );
    }

    #[Computed]
    public function laborPositionOptions(): array
    {
        return $this->positionOptionsFor(
            searchKey: 'searchLaborPosition',
            selectedId: $this->laborSelected('position_id')
        );
    }

    #[Computed]
    public function educationDegreeOptions(): array
    {
        $locale = app()->getLocale();
        $labelColumn = 'title_'.$locale;

        $base = EducationDegree::query()
            ->select('id', DB::raw("$labelColumn as label"));

        $search = $this->dropdownSearch('searchEducationDegree');
        $selected = $this->dropdownSelected('education_degree_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education_degree:{$locale}",
                base: $base,
                selectedId: $selected,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 40
        );
    }

    #[Computed]
    public function workNormOptions(): array
    {
        $locale = app()->getLocale();
        $labelColumn = 'name_'.$locale;

        $base = WorkNorm::query()
            ->select('id', DB::raw("$labelColumn as label"));

        $search = $this->dropdownSearch('searchWorkNorm');
        $selected = $this->dropdownSelected('work_norm_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:work_norms:{$locale}",
                base: $base,
                selectedId: $selected,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 40
        );
    }

    #[Computed]
    public function socialOriginOptions(): array
    {
        $base = SocialOrigin::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch('searchSocialOrigin');
        $selected = $this->dropdownSelected('social_origin_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:social_origin',
                base: $base,
                selectedId: $selected,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 40
        );
    }

    #[Computed]
    public function disabilityOptions(): array
    {
        if (! $this->isDisabilityEnabled()) {
            return [];
        }

        $base = Disability::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch('searchDisability');
        $selected = $this->dropdownSelected('disability_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:disabilities',
                base: $base,
                selectedId: $selected,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 40
        );
    }

    protected function structureOptionsFor(string $searchKey, int|string|null $selectedId): array
    {
        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->orderBy('level')
            ->orderBy('code');

        $search = $this->dropdownSearch($searchKey);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:structures',
                base: $base,
                selectedId: $selectedId,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 40
        );
    }

    protected function positionOptionsFor(string $searchKey, int|string|null $selectedId): array
    {
        $base = Position::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch($searchKey);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:positions',
                base: $base,
                selectedId: $selectedId,
                limit: 40
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 40
        );
    }
}
