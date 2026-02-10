<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Models\Award;
use App\Models\City;
use App\Models\Country;
use App\Models\Disability;
use App\Models\EducationalInstitution;
use App\Models\EducationDegree;
use App\Models\EducationDocumentType;
use App\Models\EducationForm as EducationFormModel;
use App\Models\EducationType;
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
use App\Modules\Personnel\Support\Traits\Personnel\ResolvesPersonnelLabelCache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait PersonnelDropdownOptions
{
    use ResolvesPersonnelLabelCache;

    #[Computed(persist: true)]
    public function nationalityOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchNationality'),
            selectedId: $this->dropdownSelected('nationality_id')
        );
    }

    #[Computed(persist: true)]
    public function previousNationalityOptions(): array
    {
        $search = $this->dropdownSearch('searchPreviousNationality');

        return $this->countryOptions(
            searchTerm: $search,
            selectedId: $this->dropdownSelected('previous_nationality_id'),
            cacheKeySuffix: 'previous'
        );
    }

    #[Computed(persist: true)]
    public function documentNationalityOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchDocumentNationality'),
            selectedId: $this->documentValue('nationality_id'),
            cacheKeySuffix: 'document-nationality'
        );
    }

    #[Computed(persist: true)]
    public function documentBornCountryOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchDocumentBornCountry'),
            selectedId: $this->documentValue('born_country_id'),
            cacheKeySuffix: 'document-born-country'
        );
    }

    #[Computed(persist: true)]
    public function documentCityOptions(): array
    {
        $base = City::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($countryId = $this->documentValue('born_country_id')) {
            $base->where('country_id', $countryId);
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchDocumentCity'),
            selectedId: $this->documentValue('born_city_id'),
            limit: 80
        );
    }

    #[Computed(persist: true)]
    public function educationInstitutionOptions(): array
    {
        return $this->educationInstitutionOptionsFor(
            search: $this->dropdownSearch('searchEducationInstitution'),
            selectedId: $this->educationSelected('educational_institution_id'),
            cacheSuffix: 'primary'
        );
    }

    #[Computed(persist: true)]
    public function extraEducationInstitutionOptions(): array
    {
        return $this->educationInstitutionOptionsFor(
            search: $this->dropdownSearch('searchExtraEducationInstitution'),
            selectedId: $this->educationSelected('educational_institution_id', true),
            cacheSuffix: 'extra'
        );
    }

    protected function educationInstitutionOptionsFor(string $search, $selectedId, string $cacheSuffix): array
    {
        $base = EducationalInstitution::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education:institutions:{$cacheSuffix}",
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

    #[Computed(persist: true)]
    public function educationFormOptions(): array
    {
        return $this->educationFormOptionsFor(
            search: $this->dropdownSearch('searchEducationForm'),
            selectedId: $this->educationSelected('education_form_id'),
            cacheSuffix: 'primary'
        );
    }

    #[Computed(persist: true)]
    public function extraEducationFormOptions(): array
    {
        return $this->educationFormOptionsFor(
            search: $this->dropdownSearch('searchExtraEducationForm'),
            selectedId: $this->educationSelected('education_form_id', true),
            cacheSuffix: 'extra'
        );
    }

    protected function educationFormOptionsFor(string $search, $selectedId, string $cacheSuffix): array
    {
        $locale = app()->getLocale();
        $labelColumn = "name_{$locale}";

        $base = EducationFormModel::query()
            ->select('id', DB::raw("$labelColumn as label"))
            ->orderBy($labelColumn);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education:forms:{$cacheSuffix}:{$locale}",
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

    #[Computed(persist: true)]
    public function educationTypeOptions(): array
    {
        $base = EducationType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($this->dropdownSearch('searchEducationType') === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:education:types',
                base: $base,
                selectedId: $this->educationSelected('education_type_id', true),
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchEducationType'),
            selectedId: $this->educationSelected('education_type_id', true),
            limit: 60
        );
    }

    #[Computed(persist: true)]
    public function educationDocumentTypeOptions(): array
    {
        $base = EducationDocumentType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($this->dropdownSearch('searchEducationDocumentType') === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:education:document_types',
                base: $base,
                selectedId: $this->educationSelected('education_document_type_id', true),
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchEducationDocumentType'),
            selectedId: $this->educationSelected('education_document_type_id', true),
            limit: 60
        );
    }

    #[Computed(persist: true)]
    public function rankOptions(): array
    {
        return $this->rankOptionsFor(
            search: $this->dropdownSearch('searchRank'),
            selectedId: $this->currentRankSelection('rank_id')
        );
    }

    #[Computed(persist: true)]
    public function militaryRankOptions(): array
    {
        return $this->rankOptionsFor(
            search: $this->dropdownSearch('searchMilitaryRank'),
            selectedId: $this->historyFormSelection('rank_id')
        );
    }

    #[Computed(persist: true)]
    public function rankReasonOptions(): array
    {
        return $this->rankReasonOptionsFor(
            search: $this->dropdownSearch('searchRankReason'),
            selectedId: $this->currentRankSelection('rank_reason_id')
        );
    }

    #[Computed(persist: true)]
    public function awardOptions(): array
    {
        return $this->awardOptionsFor(
            search: $this->dropdownSearch('searchAward'),
            selectedId: $this->currentAwardSelection()
        );
    }

    #[Computed(persist: true)]
    public function punishmentOptions(): array
    {
        return $this->punishmentOptionsFor(
            search: $this->dropdownSearch('searchPunishment'),
            selectedId: $this->currentPunishmentSelection()
        );
    }

    #[Computed(persist: true)]
    public function kinshipOptions(): array
    {
        return $this->kinshipOptionsFor(
            search: $this->dropdownSearch('searchKinship'),
            selectedId: $this->currentKinshipSelection()
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

    #[Computed(persist: true)]
    public function languageOptions(): array
    {
        return $this->languageOptionsFor(
            search: $this->dropdownSearch('searchLanguage'),
            selectedId: $this->currentLanguageSelection()
        );
    }

    #[Computed(persist: true)]
    public function scientificDegreeOptions(): array
    {
        return $this->scientificDegreeOptionsFor(
            search: $this->dropdownSearch('searchDegree'),
            selectedId: $this->currentDegreeSelection()
        );
    }

    #[Computed(persist: true)]
    public function step8DocumentTypeOptions(): array
    {
        return $this->step8DocumentTypeOptionsFor(
            search: $this->dropdownSearch('searchDegreeDocumentType'),
            selectedId: $this->currentDegreeDocumentSelection()
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

    #[Computed(persist: true)]
    public function structureOptions(): array
    {
        return $this->structureOptionsFor(
            searchKey: 'searchStructure',
            selectedId: $this->dropdownSelected('structure_id')
        );
    }

    #[Computed(persist: true)]
    public function laborStructureOptions(): array
    {
        return $this->structureOptionsFor(
            searchKey: 'searchLaborStructure',
            selectedId: $this->laborSelected('structure_id')
        );
    }

    #[Computed(persist: true)]
    public function positionOptions(): array
    {
        return $this->positionOptionsFor(
            searchKey: 'searchPosition',
            selectedId: $this->dropdownSelected('position_id')
        );
    }

    #[Computed(persist: true)]
    public function laborPositionOptions(): array
    {
        return $this->positionOptionsFor(
            searchKey: 'searchLaborPosition',
            selectedId: $this->laborSelected('position_id')
        );
    }

    #[Computed(persist: true)]
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
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 60
        );
    }

    #[Computed(persist: true)]
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
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed(persist: true)]
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
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed(persist: true)]
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
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    protected function dropdownSelected(string $key): int|string|null
    {
        if (property_exists($this, 'personalForm') && $this->personalForm) {
            return data_get($this->personalForm->personnel, $key);
        }

        return null;
    }

    protected function educationSelected(string $key, bool $isExtra = false): int|string|null
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return null;
        }

        $source = $isExtra ? $this->educationForm->extraEducation : $this->educationForm->education;

        return data_get($source, $key);
    }

    protected function currentRankSelection(string $path)
    {
        if (property_exists($this, 'laborActivityForm') && $this->laborActivityForm) {
            return data_get($this->laborActivityForm->rank ?? [], $path);
        }

        return null;
    }

    protected function laborSelected(string $key): int|string|null
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return null;
        }

        return data_get($this->laborActivityForm->laborActivity ?? [], $key);
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

    protected function currentAwardSelection()
    {
        $form = $this->awardsPunishmentsFormInstance();

        if ($form) {
            return data_get($form->award ?? [], 'award_id');
        }

        return null;
    }

    protected function currentPunishmentSelection()
    {
        $form = $this->awardsPunishmentsFormInstance();

        if ($form) {
            return data_get($form->punishment ?? [], 'punishment_id');
        }

        return null;
    }

    protected function currentKinshipSelection()
    {
        $form = $this->kinshipFormInstance();

        if ($form) {
            return data_get($form->kinship ?? [], 'kinship_id');
        }

        return null;
    }

    protected function currentLanguageSelection()
    {
        $form = $this->miscFormInstance();

        if ($form) {
            return data_get($form->language ?? [], 'language_id');
        }

        return null;
    }

    protected function currentDegreeSelection()
    {
        $form = $this->miscFormInstance();

        if ($form) {
            return data_get($form->degree ?? [], 'degree_and_name_id');
        }

        return null;
    }

    protected function currentDegreeDocumentSelection()
    {
        $form = $this->miscFormInstance();

        if ($form) {
            return data_get($form->degree ?? [], 'edu_doc_type_id');
        }

        return null;
    }

    protected function historyFormSelection(string $path)
    {
        if (property_exists($this, 'historyForm') && $this->historyForm) {
            return data_get($this->historyForm->military ?? [], $path);
        }

        return null;
    }

    protected function documentValue(string $key): mixed
    {
        if (property_exists($this, 'documentPayload') && method_exists($this, 'documentPayload')) {
            return data_get($this->documentPayload(), "document.{$key}");
        }

        if (property_exists($this, 'documentForm') && $this->documentForm) {
            return data_get($this->documentForm->document, $key);
        }

        return null;
    }

    protected function isDisabilityEnabled(): bool
    {
        return property_exists($this, 'personalForm')
            && $this->personalForm
            && (bool) $this->personalForm->hasDisability;
    }

    protected function countryOptions(string $searchTerm, $selectedId, string $cacheKeySuffix = 'current'): array
    {
        $locale = app()->getLocale();

        $base = Country::query()
            ->select('countries.id', DB::raw('ct.title as label'))
            ->join('country_translations as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'countries.id')
                    ->where('ct.locale', $locale);
            })
            ->orderBy('ct.title');

        if ($searchTerm === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:country:{$cacheKeySuffix}:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'ct.title',
            searchTerm: $searchTerm,
            selectedId: $selectedId,
            limit: 80
        );
    }
}
