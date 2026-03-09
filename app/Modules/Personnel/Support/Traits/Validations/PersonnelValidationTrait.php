<?php

namespace App\Modules\Personnel\Support\Traits\Validations;

trait PersonnelValidationTrait
{
    public function validationRules(): array
    {
        return [
            1 => $this->getPersonalInfoRules(),
            2 => $this->getDocumentRules(),
            3 => $this->getEducationRules(),
            4 => $this->getCareerRules(),
            5 => $this->getMilitaryAndHealthRules(),
            6 => $this->getRewardsAndPunishmentsRules(),
            7 => $this->getKinshipRules(),
            8 => $this->getMiscellaneousRules(),
        ];
    }

    protected function getPersonalInfoRules(): array
    {
        $uniqueTableRule = 'required|min:1|unique:personnels,tabel_no' .
            ($this->resolvePersonnelId() ? ',' . $this->resolvePersonnelId() : '');

        $personnelState = $this->resolvePersonnelState();
        $hasChangedInitials = (bool) data_get($personnelState, 'has_changed_initials', false);
        $hasChangedNationality = (bool) data_get($personnelState, 'has_changed_nationality', false);
        $hasDisability = property_exists($this, 'personalForm') && $this->personalForm
            ? (bool) $this->personalForm->hasDisability
            : false;

        $initialsChangeRules = $hasChangedInitials ? [
            'personalForm.personnel.previous_name' => 'required|min:3',
            'personalForm.personnel.previous_surname' => 'required|min:3',
            'personalForm.personnel.previous_patronymic' => 'required|min:3',
            'personalForm.personnel.initials_changed_date' => 'required|date',
            'personalForm.personnel.initials_change_reason' => 'required|min:3',
        ] : [];

        $nationalityChangeRules = $hasChangedNationality ? [
            'personalForm.personnel.previous_nationality_id' => 'required|int|exists:countries,id',
            'personalForm.personnel.nationality_changed_date' => 'required|date',
            'personalForm.personnel.nationality_change_reason' => 'required|min:3',
        ] : [];

        $disabilityRules = $hasDisability ? [
            'personalForm.personnel.disability_id' => 'required|int|exists:disabilities,id',
            'personalForm.personnel.disability_given_date' => 'required|date',
        ] : [];

        return array_merge([
            'personalForm.personnel.tabel_no' => $uniqueTableRule,
            'personalForm.personnel.name' => 'required|min:3',
            'personalForm.personnel.surname' => 'required|min:3',
            'personalForm.personnel.patronymic' => 'required|min:3',
            'personalForm.personnel.birthdate' => 'required|date',
            'personalForm.personnel.gender' => 'required|int',
            'personalForm.personnel.nationality_id' => 'required|int|exists:countries,id',
            'personalForm.personnel.mobile' => ['required', 'min:7'],
            'personalForm.personnel.pin' => 'required|min:7|max:7',
            'personalForm.personnel.residental_address' => 'required|min:3',
            'personalForm.personnel.registered_address' => 'required|min:3',
            'personalForm.personnel.education_degree_id' => 'required|int|exists:education_degrees,id',
            'personalForm.personnel.structure_id' => 'required|int',
            'personalForm.personnel.position_id' => 'required|int',
            'personalForm.personnel.work_norm_id' => 'required|int|exists:work_norms,id',
            'personalForm.personnel.join_work_date' => 'required|date',
        ], $initialsChangeRules, $nationalityChangeRules, $disabilityRules);
    }

    protected function getDocumentRules(): array
    {
        $rules = [];

        if ($this->shouldValidateDocumentBlock()) {
            $rules = array_merge($rules, $this->documentRuleSet());
        }

        if ($this->shouldValidateServiceCardBlock()) {
            $rules = array_merge($rules, $this->serviceCardRuleSet());
        }

        if ($this->shouldValidatePassportBlock()) {
            $rules = array_merge($rules, $this->passportRuleSet());
        }

        return $rules;
    }

    protected function documentRuleSet(): array
    {
        return [
            'documentForm.document.pin' => 'required|min:7',
            'documentForm.document.nationality_id' => 'required|int|exists:countries,id',
            'documentForm.document.series' => 'required|min:1',
            'documentForm.document.number' => 'required|int',
            'documentForm.document.born_country_id' => 'required|int|exists:countries,id',
            'documentForm.document.born_city_id' => 'required|int|exists:cities,id',
            'documentForm.document.is_married' => 'required|boolean',
            'documentForm.document.height' => 'required|int',
        ];
    }

    protected function serviceCardRuleSet(): array
    {
        return [
            'documentForm.serviceCards.card_number' => 'required|string|min:3',
            'documentForm.serviceCards.given_date' => 'required|date',
            'documentForm.serviceCards.valid_date' => 'required|date',
        ];
    }

    protected function passportRuleSet(): array
    {
        return [
            'documentForm.passports.serial_number' => 'required|string|min:3',
            'documentForm.passports.valid_date' => 'required|date',
            'documentForm.passports.given_date' => 'required|date',
        ];
    }

    protected function educationRuleSet(): array
    {
        return [
            'educationForm.education.educational_institution_id' => 'required|int|exists:educational_institutions,id',
            'educationForm.education.education_form_id' => 'required|int|exists:education_forms,id',
            'educationForm.education.education_language' => 'required|min:2',
            'educationForm.education.specialty' => 'required|min:2',
            'educationForm.education.admission_year' => 'required|date',
            'educationForm.education.graduated_year' => 'required|date|after:educationForm.education.admission_year',
            'educationForm.education.profession_by_document' => 'required|min:2',
            'educationForm.education.diplom_serie' => 'required|min:1',
            'educationForm.education.diplom_no' => 'required|int',
            'educationForm.education.diplom_given_date' => 'required|date',
        ];
    }

    protected function extraEducationRuleSet(): array
    {
        return [
            'educationForm.extraEducation.education_type_id' => 'required|int|exists:education_types,id',
            'educationForm.extraEducation.educational_institution_id' => 'required|int|exists:educational_institutions,id',
            'educationForm.extraEducation.education_form_id' => 'required|int|exists:education_forms,id',
            'educationForm.extraEducation.name' => 'required|min:2',
            'educationForm.extraEducation.shortname' => 'required|min:2',
            'educationForm.extraEducation.education_language' => 'required|min:2',
            'educationForm.extraEducation.education_program_name' => 'required|min:2',
            'educationForm.extraEducation.admission_year' => 'required|date',
            'educationForm.extraEducation.graduated_year' => 'required|date|after:educationForm.extraEducation.admission_year',
            'educationForm.extraEducation.education_document_type_id' => 'required|int|exists:education_document_types,id',
            'educationForm.extraEducation.diplom_serie' => 'required|min:1',
            'educationForm.extraEducation.diplom_no' => 'required|int|unique:personnel_extra_education,diplom_no',
            'educationForm.extraEducation.diplom_given_date' => 'required|date',
        ];
    }

    protected function resolvePersonnelState(): array
    {
        if (property_exists($this, 'personalForm') && $this->personalForm) {
            return $this->personalForm->personnel ?? [];
        }

        return [];
    }

    protected function resolvePersonnelId(): ?int
    {
        if (property_exists($this, 'personnelModelData') && $this->personnelModelData) {
            return $this->personnelModelData->id ?? null;
        }

        if (property_exists($this, 'updatePersonnel') && ! empty($this->updatePersonnel['id'])) {
            return (int) $this->updatePersonnel['id'];
        }

        if (property_exists($this, 'personnelModel') && $this->personnelModel) {
            return is_numeric($this->personnelModel) ? (int) $this->personnelModel : null;
        }

        return null;
    }

    protected function getEducationRules(): array
    {
        $rules = $this->educationRuleSet();

        if ($this->educationFormHasExtraEducation()) {
            $rules = array_merge($rules, $this->extraEducationRuleSet());
        }

        return $rules;
    }

    protected function educationFormHasExtraEducation(): bool
    {
        return property_exists($this, 'educationForm')
            && $this->educationForm
            && (bool) ($this->educationForm->hasExtraEducation ?? false);
    }

    protected function getCareerRules(): array
    {
        $rules = [];

        if ($this->shouldValidateLaborActivityDraft()) {
            $rules = $this->laborActivityRuleSet();

            if ($this->isSpecialServiceEnabled()) {
                $rules = array_merge($rules, $this->laborActivitySpecialServiceRules());
            }
        }

        if ($this->isAddedRank) {
            $rules = array_merge($rules, $this->rankRuleSet());
        }

        return $rules;
    }

    protected function getMilitaryAndHealthRules(): array
    {
        return [
            'historyForm.military.rank_id' => 'required|int|exists:ranks,id',
            'historyForm.military.attitude_to_military_service' => 'required|min:2',
            'historyForm.military.given_date' => 'required|date',
            'historyForm.injury.injury_type' => 'required',
            'historyForm.injury.location' => 'required|min:2',
            'historyForm.injury.date_time' => 'required|date',
            'historyForm.captivity.location' => 'required|min:2',
            'historyForm.captivity.condition' => 'required|min:2',
            'historyForm.captivity.taken_captive_date' => 'required|date',
        ];
    }

    protected function getRewardsAndPunishmentsRules(): array
    {
        return array_merge(
            $this->awardRuleSet(),
            $this->punishmentRuleSet()
        );
    }

    protected function awardRuleSet(): array
    {
        return [
            'awardsPunishmentsForm.award.award_id' => 'required|int|exists:awards,id',
            'awardsPunishmentsForm.award.reason' => 'required|min:2',
            'awardsPunishmentsForm.award.given_date' => 'required|date',
        ];
    }

    protected function punishmentRuleSet(): array
    {
        return [
            'awardsPunishmentsForm.punishment.punishment_id' => 'required|int|exists:punishments,id',
            'awardsPunishmentsForm.punishment.reason' => 'required|min:2',
            'awardsPunishmentsForm.punishment.given_date' => 'required|date',
        ];
    }

    protected function languageRuleSet(): array
    {
        return [
            'miscForm.language.language_id' => 'required|int|exists:languages,id',
            'miscForm.language.knowledge_status' => 'required|min:1',
        ];
    }

    protected function eventRuleSet(): array
    {
        return [
            'miscForm.event.event_type' => 'required|min:2',
            'miscForm.event.event_name' => 'required|min:2',
            'miscForm.event.event_date' => 'required|date',
        ];
    }

    protected function degreeRuleSet(): array
    {
        return [
            'miscForm.degree.degree_and_name_id' => 'required|int|exists:education_degrees,id',
            'miscForm.degree.science' => 'required|min:2',
            'miscForm.degree.given_date' => 'required|date',
            'miscForm.degree.subject' => 'required|min:2',
            'miscForm.degree.edu_doc_type_id' => 'required|int|exists:education_document_types,id',
            'miscForm.degree.diplom_serie' => 'required|min:1',
            'miscForm.degree.diplom_no' => 'required|int',
            'miscForm.degree.diplom_given_date' => 'required|date',
            'miscForm.degree.document_issued_by' => 'required|min:2',
        ];
    }

    protected function electionRuleSet(): array
    {
        return [
            'miscForm.election.election_type' => 'required|min:1',
            'miscForm.election.location' => 'required|min:2',
            'miscForm.election.elected_date' => 'required|date',
        ];
    }

    protected function getKinshipRules(): array
    {
        return [
            'kinshipForm.kinship.kinship_id' => 'required|int|exists:kinships,id',
            'kinshipForm.kinship.fullname' => 'required|min:2',
            'kinshipForm.kinship.birthdate' => 'required|date',
            'kinshipForm.kinship.registered_address' => 'required|min:2',
            'kinshipForm.kinship.residental_address' => 'required|min:2',
        ];
    }

    protected function getMiscellaneousRules(): array
    {
        $rules = array_merge(
            $this->languageRuleSet(),
            $this->eventRuleSet(),
            $this->degreeRuleSet()
        );

        if ($this->miscFormHasElections()) {
            $rules = array_merge($rules, $this->electionRuleSet());
        }

        return $rules;
    }

    protected function laborActivityRuleSet(): array
    {
        $useLookup = (bool) data_get($this->laborActivityForm?->laborActivity ?? [], 'use_lookup');

        $rules = [
            'laborActivityForm.laborActivity.company_name' => 'required|min:2',
            'laborActivityForm.laborActivity.join_date' => 'required|date',
        ];

        if ($useLookup) {
            $rules['laborActivityForm.laborActivity.position_id'] = 'required|exists:positions,id';
            $rules['laborActivityForm.laborActivity.structure_id'] = 'required|exists:structures,id';
        } else {
            $rules['laborActivityForm.laborActivity.position'] = 'required|min:2';
        }

        return $rules;
    }

    protected function laborActivitySpecialServiceRules(): array
    {
        return [
            'laborActivityForm.laborActivity.coefficient' => 'required|numeric|min:0',
            'laborActivityForm.laborActivity.order_given_by' => 'required|min:2',
            'laborActivityForm.laborActivity.order_no' => 'required|min:2',
            'laborActivityForm.laborActivity.order_date' => 'required|date',
        ];
    }

    protected function rankRuleSet(): array
    {
        return [
            'laborActivityForm.rank.rank_id' => 'required|int|exists:ranks,id',
            'laborActivityForm.rank.name' => 'required|min:2',
            'laborActivityForm.rank.given_date' => 'required|date',
            'laborActivityForm.rank.order_no' => 'required|string|min:1',
            'laborActivityForm.rank.order_given_by' => 'required|string|min:1',
            'laborActivityForm.rank.order_date' => 'required|date',
        ];
    }

    protected function miscFormHasElections(): bool
    {
        if (property_exists($this, 'miscForm') && $this->miscForm) {
            return (bool) $this->miscForm->hasElectedElectorals;
        }

        return false;
    }

    protected function isSpecialServiceEnabled(): bool
    {
        if (property_exists($this, 'isSpecialService')) {
            return (bool) $this->isSpecialService;
        }

        return false;
    }

    protected function shouldValidateLaborActivityDraft(): bool
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return false;
        }

        $draft = $this->laborActivityForm->laborActivity ?? [];

        return filled($draft['company_name'] ?? null)
            || filled($draft['position'] ?? null)
            || filled($draft['join_date'] ?? null);
    }

    protected function validationAttributes(): array
    {
        return [
            'personalForm.personnel.tabel_no' => __('personnel::common.labels.tabel_no'),
            'personalForm.personnel.name' => __('personnel::common.labels.name'),
            'personalForm.personnel.surname' => __('personnel::common.labels.surname'),
            'personalForm.personnel.patronymic' => __('personnel::common.labels.patronymic'),
            'personalForm.personnel.birthdate' => __('personnel::common.labels.birthdate'),
            'personalForm.personnel.previous_name' => __('personnel::common.labels.previous_name'),
            'personalForm.personnel.previous_surname' => __('personnel::common.labels.previous_surname'),
            'personalForm.personnel.previous_patronymic' => __('personnel::common.labels.previous_patronymic'),
            'personalForm.personnel.gender' => __('personnel::common.labels.gender'),
            'personalForm.personnel.initials_changed_date' => __('personnel::common.labels.change_date'),
            'personalForm.personnel.initials_change_reason' => __('personnel::common.labels.change_reason'),
            'personalForm.personnel.nationality_id' => __('personnel::common.labels.nationality'),
            'personalForm.personnel.previous_nationality_id' => __('personnel::common.labels.previous_nationality'),
            'personalForm.personnel.nationality_changed_date' => __('personnel::common.labels.nationality_change_date'),
            'personalForm.personnel.nationality_change_reason' => __('personnel::common.labels.nationality_change_reason'),
            'personalForm.personnel.phone' => __('personnel::common.labels.phone'),
            'personalForm.personnel.mobile' => __('personnel::common.labels.mobile'),
            'personalForm.personnel.email' => __('personnel::common.labels.email'),
            'personalForm.personnel.pin' => __('personnel::common.labels.pin'),
            'personalForm.personnel.residental_address' => __('personnel::common.labels.residental_address'),
            'personalForm.personnel.registered_address' => __('personnel::common.labels.registered_address'),
            'personalForm.personnel.education_degree_id' => __('personnel::common.labels.education_degree'),
            'personalForm.personnel.structure_id' => __('personnel::common.labels.structure'),
            'personalForm.personnel.position_id' => __('personnel::common.labels.position'),
            'personalForm.personnel.work_norm_id' => __('personnel::common.labels.work_norms'),
            'personalForm.personnel.join_work_date' => __('personnel::common.labels.join_work_date'),
            'personalForm.personnel.disability_id' => __('personnel::common.labels.disability'),
            'personalForm.personnel.disability_given_date' => __('personnel::common.labels.disability_given_date'),
            'documentForm.document.pin' => __('personnel::common.labels.pin'),
            'documentForm.document.nationality_id' => __('personnel::common.labels.nationality'),
            'documentForm.document.series' => __('personnel::common.labels.series'),
            'documentForm.document.number' => __('personnel::common.labels.document_number'),
            'documentForm.document.born_country_id' => __('personnel::common.labels.born_country'),
            'documentForm.document.born_city_id' => __('personnel::common.labels.city'),
            'documentForm.document.is_married' => __('personnel::common.labels.family_status'),
            'documentForm.document.height' => __('personnel::common.labels.height'),
            'documentForm.serviceCards.card_number' => __('personnel::common.labels.card_number'),
            'documentForm.serviceCards.valid_date' => __('personnel::common.labels.valid_date'),
            'documentForm.serviceCards.given_date' => __('personnel::common.labels.given_date'),
            'documentForm.passports.serial_number' => __('personnel::common.labels.serial_number'),
            'documentForm.passports.valid_date' => __('personnel::common.labels.valid_date'),
            'documentForm.passports.given_date' => __('personnel::common.labels.given_date'),
            'educationForm.education.educational_institution_id' => __('personnel::common.labels.institution'),
            'educationForm.education.education_form_id' => __('personnel::common.labels.education_form'),
            'educationForm.education.education_language' => __('personnel::common.labels.education_language'),
            'educationForm.education.specialty' => __('personnel::common.labels.specialty'),
            'educationForm.education.admission_year' => __('personnel::common.labels.admission_year'),
            'educationForm.education.graduated_year' => __('personnel::common.labels.graduated_year'),
            'educationForm.education.profession_by_document' => __('personnel::common.labels.profession'),
            'educationForm.education.diplom_serie' => __('personnel::common.labels.diplom_serie'),
            'educationForm.education.diplom_no' => __('personnel::common.labels.diplom_no'),
            'educationForm.education.diplom_given_date' => __('personnel::common.labels.diplom_given_date'),
            'educationForm.extraEducation.education_type_id' => __('personnel::common.labels.education_type'),
            'educationForm.extraEducation.educational_institution_id' => __('personnel::common.labels.institution'),
            'educationForm.extraEducation.education_form_id' => __('personnel::common.labels.education_form'),
            'educationForm.extraEducation.name' => __('personnel::common.labels.name'),
            'educationForm.extraEducation.shortname' => __('personnel::common.labels.shortname'),
            'educationForm.extraEducation.education_language' => __('personnel::common.labels.education_language'),
            'educationForm.extraEducation.education_program_name' => __('personnel::common.labels.program_name'),
            'educationForm.extraEducation.admission_year' => __('personnel::common.labels.admission_year'),
            'educationForm.extraEducation.graduated_year' => __('personnel::common.labels.graduated_year'),
            'educationForm.extraEducation.education_document_type_id' => __('personnel::common.labels.document_type'),
            'educationForm.extraEducation.diplom_serie' => __('personnel::common.labels.diplom_serie'),
            'educationForm.extraEducation.diplom_no' => __('personnel::common.labels.diplom_no'),
            'educationForm.extraEducation.diplom_given_date' => __('personnel::common.labels.diplom_given_date'),
            'laborActivityForm.laborActivity.company_name' => __('personnel::common.labels.company_name'),
            'laborActivityForm.laborActivity.position' => __('personnel::common.labels.position'),
            'laborActivityForm.laborActivity.position_id' => __('personnel::common.labels.position'),
            'laborActivityForm.laborActivity.structure_id' => __('personnel::common.labels.structure'),
            'laborActivityForm.laborActivity.join_date' => __('personnel::common.labels.join_date'),
            'laborActivityForm.laborActivity.coefficient' => __('personnel::common.labels.coefficient'),
            'laborActivityForm.laborActivity.order_given_by' => __('personnel::common.labels.order_issued_by'),
            'laborActivityForm.laborActivity.order_no' => __('personnel::common.labels.order_number'),
            'laborActivityForm.laborActivity.order_date' => __('personnel::common.labels.order_date'),
            'laborActivityForm.rank.rank_id' => __('personnel::common.labels.rank'),
            'laborActivityForm.rank.name' => __('personnel::common.labels.name'),
            'laborActivityForm.rank.given_date' => __('personnel::common.labels.given_date'),
            'laborActivityForm.rank.order_no' => __('personnel::common.labels.order_number'),
            'laborActivityForm.rank.order_given_by' => __('personnel::common.labels.order_issued_by'),
            'laborActivityForm.rank.order_date' => __('personnel::common.labels.order_date'),
            'laborActivityForm.rank.rank_reason_id' => __('personnel::common.labels.rank_reasons'),
            'historyForm.military.rank_id' => __('personnel::common.labels.rank'),
            'historyForm.military.attitude_to_military_service' => __('personnel::common.labels.attitude_to_military_service'),
            'historyForm.military.given_date' => __('personnel::common.labels.given_date'),
            'historyForm.injury.injury_type' => __('personnel::common.labels.injury_type'),
            'historyForm.injury.location' => __('personnel::common.labels.location'),
            'historyForm.injury.date_time' => __('personnel::common.labels.date'),
            'historyForm.captivity.condition' => __('personnel::common.labels.condition'),
            'historyForm.captivity.location' => __('personnel::common.labels.location'),
            'historyForm.captivity.taken_captive_date' => __('personnel::common.labels.taken_date'),
            'awardsPunishmentsForm.award.award_id' => __('personnel::common.labels.award'),
            'awardsPunishmentsForm.award.reason' => __('personnel::common.labels.reason'),
            'awardsPunishmentsForm.award.given_date' => __('personnel::common.labels.given_date'),
            'awardsPunishmentsForm.punishment.punishment_id' => __('personnel::common.labels.punishment'),
            'awardsPunishmentsForm.punishment.reason' => __('personnel::common.labels.reason'),
            'awardsPunishmentsForm.punishment.given_date' => __('personnel::common.labels.given_date'),
            'kinshipForm.kinship.kinship_id' => __('personnel::common.labels.kinship'),
            'kinshipForm.kinship.fullname' => __('personnel::common.labels.fullname'),
            'kinshipForm.kinship.birthdate' => __('personnel::common.labels.birthdate'),
            'kinshipForm.kinship.registered_address' => __('personnel::common.labels.registered_address'),
            'kinshipForm.kinship.residental_address' => __('personnel::common.labels.residental_address'),
            'miscForm.language.language_id' => __('personnel::common.labels.language'),
            'miscForm.language.knowledge_status' => __('personnel::common.labels.knowledge_status'),
            'miscForm.event.event_type' => __('personnel::common.labels.event_type'),
            'miscForm.event.event_name' => __('personnel::common.labels.event_name'),
            'miscForm.event.event_date' => __('personnel::common.labels.event_date'),
            'miscForm.degree.degree_and_name_id' => __('personnel::common.labels.degree'),
            'miscForm.degree.science' => __('personnel::common.labels.science'),
            'miscForm.degree.given_date' => __('personnel::common.labels.given_date'),
            'miscForm.degree.subject' => __('personnel::common.labels.subject'),
            'miscForm.degree.edu_doc_type_id' => __('personnel::common.labels.document_type'),
            'miscForm.degree.diplom_serie' => __('personnel::common.labels.diplom_serie'),
            'miscForm.degree.diplom_no' => __('personnel::common.labels.diplom_number'),
            'miscForm.degree.diplom_given_date' => __('personnel::common.labels.given_date'),
            'miscForm.degree.document_issued_by' => __('personnel::common.labels.document_issued_by'),
            'miscForm.election.election_type' => __('personnel::common.labels.election_type'),
            'miscForm.election.location' => __('personnel::common.labels.location'),
            'miscForm.election.elected_date' => __('personnel::common.labels.elected_date'),
        ];
    }

    protected function shouldValidateDocumentBlock(): bool
    {
        $document = property_exists($this, 'documentForm') && $this->documentForm
            ? ($this->documentForm->document ?? [])
            : [];

        return $this->hasPayloadValues($document);
    }

    protected function shouldValidateServiceCardBlock(): bool
    {
        $serviceCards = property_exists($this, 'documentForm') && $this->documentForm
            ? ($this->documentForm->serviceCards ?? [])
            : [];

        return $this->hasPayloadValues($serviceCards);
    }

    protected function shouldValidatePassportBlock(): bool
    {
        $passports = property_exists($this, 'documentForm') && $this->documentForm
            ? ($this->documentForm->passports ?? [])
            : [];

        return $this->hasPayloadValues($passports);
    }

    protected function hasPayloadValues($payload): bool
    {
        if (method_exists($this, 'payloadHasValues')) {
            return $this->payloadHasValues($payload);
        }

        if (is_array($payload)) {
            foreach ($payload as $value) {
                if ($this->hasPayloadValues($value)) {
                    return true;
                }
            }

            return false;
        }

        return $payload !== null && $payload !== '';
    }
}
