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
        return [
            'laborActivityForm.laborActivity.company_name' => 'required|min:2',
            'laborActivityForm.laborActivity.position' => 'required|min:2',
            'laborActivityForm.laborActivity.join_date' => 'required|date',
        ];
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
            'personalForm.personnel.tabel_no' => __('Tabel no'),
            'personalForm.personnel.name' => __('Name'),
            'personalForm.personnel.surname' => __('Surname'),
            'personalForm.personnel.patronymic' => __('Patronymic'),
            'personalForm.personnel.birthdate' => __('Birthdate'),
            'personalForm.personnel.previous_name' => __('Previous name'),
            'personalForm.personnel.previous_surname' => __('Previous surname'),
            'personalForm.personnel.previous_patronymic' => __('Previous patronymic'),
            'personalForm.personnel.gender' => __('Gender'),
            'personalForm.personnel.initials_changed_date' => __('Change date'),
            'personalForm.personnel.initials_change_reason' => __('Change reason'),
            'personalForm.personnel.nationality_id' => __('Nationality'),
            'personalForm.personnel.previous_nationality_id' => __('Previous nationality'),
            'personalForm.personnel.nationality_changed_date' => __('Nationality change date'),
            'personalForm.personnel.nationality_change_reason' => __('Nationality change reason'),
            'personalForm.personnel.phone' => __('Phone'),
            'personalForm.personnel.mobile' => __('Mobile'),
            'personalForm.personnel.email' => __('Email'),
            'personalForm.personnel.pin' => __('PIN'),
            'personalForm.personnel.residental_address' => __('Residental address'),
            'personalForm.personnel.registered_address' => __('Registered address'),
            'personalForm.personnel.education_degree_id' => __('Education degree'),
            'personalForm.personnel.structure_id' => __('Structure'),
            'personalForm.personnel.position_id' => __('Position'),
            'personalForm.personnel.work_norm_id' => __('Work norm'),
            'personalForm.personnel.join_work_date' => __('Join work date'),
            'personalForm.personnel.disability_id' => __('Disability'),
            'personalForm.personnel.disability_given_date' => __('Disability given date'),
            'documentForm.document.pin' => __('Pin'),
            'documentForm.document.nationality_id' => __('Nationality'),
            'documentForm.document.series' => __('Series'),
            'documentForm.document.number' => __('Document number'),
            'documentForm.document.born_country_id' => __('Born country'),
            'documentForm.document.born_city_id' => __('City'),
            'documentForm.document.is_married' => __('Family status'),
            'documentForm.document.height' => __('Height'),
            'documentForm.serviceCards.card_number' => __('Service cards'),
            'documentForm.serviceCards.valid_date' => __('Valid date'),
            'documentForm.serviceCards.given_date' => __('Given date'),
            'documentForm.passports.serial_number' => __('Serial number'),
            'documentForm.passports.valid_date' => __('Valid date'),
            'documentForm.passports.given_date' => __('Given date'),
            'educationForm.education.educational_institution_id' => __('Institution'),
            'educationForm.education.education_form_id' => __('Education form'),
            'educationForm.education.education_language' => __('Education language'),
            'educationForm.education.specialty' => __('Specialty'),
            'educationForm.education.admission_year' => __('Admission year'),
            'educationForm.education.graduated_year' => __('Graduated year'),
            'educationForm.education.profession_by_document' => __('Profession'),
            'educationForm.education.diplom_serie' => __('Diplom serie'),
            'educationForm.education.diplom_no' => __('Diplom no'),
            'educationForm.education.diplom_given_date' => __('Diplom given date'),
            'educationForm.extraEducation.education_type_id' => __('Education type'),
            'educationForm.extraEducation.educational_institution_id' => __('Institution'),
            'educationForm.extraEducation.education_form_id' => __('Education form'),
            'educationForm.extraEducation.name' => __('Name'),
            'educationForm.extraEducation.shortname' => __('Shortname'),
            'educationForm.extraEducation.education_language' => __('Education language'),
            'educationForm.extraEducation.education_program_name' => __('Program name'),
            'educationForm.extraEducation.admission_year' => __('Admission year'),
            'educationForm.extraEducation.graduated_year' => __('Graduated year'),
            'educationForm.extraEducation.education_document_type_id' => __('Document type'),
            'educationForm.extraEducation.diplom_serie' => __('Diplom serie'),
            'educationForm.extraEducation.diplom_no' => __('Diplom no'),
            'educationForm.extraEducation.diplom_given_date' => __('Diplom given date'),
            'laborActivityForm.laborActivity.company_name' => __('Company'),
            'laborActivityForm.laborActivity.position' => __('Position'),
            'laborActivityForm.laborActivity.join_date' => __('Join date'),
            'laborActivityForm.laborActivity.coefficient' => __('Coefficient'),
            'laborActivityForm.laborActivity.order_given_by' => __('Order issued by'),
            'laborActivityForm.laborActivity.order_no' => __('Order number'),
            'laborActivityForm.laborActivity.order_date' => __('Order date'),
            'laborActivityForm.rank.rank_id' => __('Rank'),
            'laborActivityForm.rank.name' => __('Name'),
            'laborActivityForm.rank.given_date' => __('Given date'),
            'laborActivityForm.rank.order_no' => __('Order number'),
            'laborActivityForm.rank.order_given_by' => __('Given by'),
            'laborActivityForm.rank.order_date' => __('Date'),
            'laborActivityForm.rank.rank_reason_id' => __('Rank reasons'),
            'historyForm.military.rank_id' => __('Rank'),
            'historyForm.military.attitude_to_military_service' => __('Attitude'),
            'historyForm.military.given_date' => __('Given date'),
            'historyForm.injury.injury_type' => __('Injury type'),
            'historyForm.injury.location' => __('Location'),
            'historyForm.injury.date_time' => __('Date'),
            'historyForm.captivity.condition' => __('Condition'),
            'historyForm.captivity.location' => __('Location'),
            'historyForm.captivity.taken_captive_date' => __('Taken date'),
            'awardsPunishmentsForm.award.award_id' => __('Award'),
            'awardsPunishmentsForm.award.reason' => __('Reason'),
            'awardsPunishmentsForm.award.given_date' => __('Given date'),
            'awardsPunishmentsForm.punishment.punishment_id' => __('Punishment'),
            'awardsPunishmentsForm.punishment.reason' => __('Reason'),
            'awardsPunishmentsForm.punishment.given_date' => __('Given date'),
            //            'criminal.punishment_id.id' => __('Criminal'),
            //            'criminal.reason' => __('Reason'),
            //            'criminal.given_date' => __('Given date'),
            'kinshipForm.kinship.kinship_id' => __('Kinship'),
            'kinshipForm.kinship.fullname' => __('Fullname'),
            'kinshipForm.kinship.birthdate' => __('Birthdate'),
            'kinshipForm.kinship.registered_address' => __('Registered address'),
            'kinshipForm.kinship.residental_address' => __('Residental address'),
            'miscForm.language.language_id' => __('Language'),
            'miscForm.language.knowledge_status' => __('Knowledge'),
            'miscForm.event.event_type' => __('Event type'),
            'miscForm.event.event_name' => __('Event name'),
            'miscForm.event.event_date' => __('Event date'),
            'miscForm.degree.degree_and_name_id' => __('Degree'),
            'miscForm.degree.science' => __('Science'),
            'miscForm.degree.given_date' => __('Given date'),
            'miscForm.degree.subject' => __('Subject'),
            'miscForm.degree.edu_doc_type_id' => __('Document type'),
            'miscForm.degree.diplom_serie' => __('Diplom serie'),
            'miscForm.degree.diplom_no' => __('Diplom number'),
            'miscForm.degree.diplom_given_date' => __('Given date'),
            'miscForm.degree.document_issued_by' => __('Issued by'),
            'miscForm.election.election_type' => __('Election type'),
            'miscForm.election.location' => __('Location'),
            'miscForm.election.elected_date' => __('Election date'),
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
