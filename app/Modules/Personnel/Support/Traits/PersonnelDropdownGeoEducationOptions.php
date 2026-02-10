<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Models\City;
use App\Models\Country;
use App\Models\EducationalInstitution;
use App\Models\EducationDocumentType;
use App\Models\EducationForm as EducationFormModel;
use App\Models\EducationType;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait PersonnelDropdownGeoEducationOptions
{
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
