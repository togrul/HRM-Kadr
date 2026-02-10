<?php

namespace App\Modules\Personnel\Support\Traits\Personnel;

use Illuminate\Support\Arr;

trait ManagesPersonnelRelationRows
{
    public function addServiceCard(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->validate($this->serviceCardRuleSet());

        $this->documentForm->addServiceCardEntry();
    }

    public function removeServiceCard(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->removeServiceCardEntry($key);
    }

    public function forceDeleteServiceCard(int $key): void
    {
        $this->removeServiceCard($key);
    }

    public function addPassport(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->validate($this->passportRuleSet());

        $this->documentForm->addPassportEntry();
    }

    public function removePassport(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->removePassportEntry($key);
    }

    public function forceDeletePassport(int $key): void
    {
        $this->removePassport($key);
    }

    public function addExtraEducation(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->validate($this->extraEducationRuleSet());

        $this->educationForm->addExtraEducationEntry(
            $this->educationCoefficientValue()
        );

        $this->recalculateEducationDurations();
    }

    public function removeExtraEducation(int $key): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->educationForm->removeExtraEducationEntry($key);

        $this->recalculateEducationDurations();
    }

    public function addLaborActivity(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->validate($this->laborActivityRuleSet());

        if (! empty($this->laborActivityForm->laborActivity['use_lookup'])) {
            $structureId = data_get($this->laborActivityForm->laborActivity, 'structure_id');
            $positionId = data_get($this->laborActivityForm->laborActivity, 'position_id');

            $this->laborActivityForm->laborActivity['structure_label'] = $this->optionLabelFor(
                $this->laborStructureOptions,
                $structureId
            );
            $this->laborActivityForm->laborActivity['position_label'] = $this->optionLabelFor(
                $this->laborPositionOptions,
                $positionId
            );
        }

        $this->laborActivityForm->addLaborActivityEntry((bool) $this->isSpecialService);

        $this->calculateSeniority();
    }

    public function forceDeleteLaborActivity(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->removeLaborActivityEntry($key);

        $this->calculateSeniority();
    }

    public function addRank(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->isAddedRank = true;
        $this->validate($this->rankRuleSet());

        $this->laborActivityForm->addRankEntry();
        $this->isAddedRank = false;
    }

    public function forceDeleteRank(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->removeRankEntry($key);
    }

    protected function calculateSeniority(?array $list = null): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            $this->calculatedData = [];

            return;
        }

        $list ??= $this->laborActivitiesWithDraft();

        if (empty($list)) {
            $this->calculatedData = [];

            return;
        }

        $this->calculatedData = $this->seniorityService()->calculateMulti($list);
    }

    protected function laborActivitiesWithDraft(): array
    {
        $list = $this->laborActivityForm->laborActivityList ?? [];

        if ($this->laborActivityDraftHasValues()) {
            $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time', 'use_lookup']);
            $list[] = $draft;
        }

        return $list;
    }

    protected function laborActivityDraftHasValues(): bool
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return false;
        }

        $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time', 'use_lookup']);

        return $this->payloadHasValues($draft);
    }

    protected function optionLabelFor(array $options, $id): ?string
    {
        foreach ($options as $option) {
            if ((int) ($option['id'] ?? 0) === (int) $id) {
                return (string) ($option['label'] ?? '');
            }
        }

        return null;
    }

    public function addMilitary(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.injury'),
            $this->exceptArray('historyForm.captivity'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addMilitaryEntry();
    }

    public function forceDeleteMilitary(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeMilitaryEntry($key);
    }

    public function addInjury(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.military'),
            $this->exceptArray('historyForm.captivity'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addInjuryEntry();
    }

    public function forceDeleteInjury(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeInjuryEntry($key);
    }

    public function addCaptivity(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.military'),
            $this->exceptArray('historyForm.injury'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addCaptivityEntry();
    }

    public function forceDeleteCaptivity(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeCaptivityEntry($key);
    }

    public function addAward(): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->awardRuleSet();

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addAwardEntry();
    }

    public function forceDeleteAward(int $key): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $form->removeAwardEntry($key);
    }

    public function addPunishment(): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->punishmentRuleSet();

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addPunishmentEntry();
    }

    public function forceDeletePunishment(int $key): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $form->removePunishmentEntry($key);
    }

    public function addKinship(): void
    {
        $form = $this->kinshipFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->getKinshipRules());

        $form->addKinshipEntry(
            $this->kinshipLabel(data_get($form->kinship, 'kinship_id'))
        );
    }

    public function forceDeleteKinship(int $key): void
    {
        $form = $this->kinshipFormInstance();

        if (! $form) {
            return;
        }

        $form->removeKinshipEntry($key);
    }

    public function addLanguage(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->languageRuleSet());

        $form->addLanguageEntry(
            $this->languageLabel(data_get($form->language, 'language_id'))
        );
    }

    public function forceDeleteLanguage(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeLanguageEntry($key);
    }

    public function addEvent(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->eventRuleSet());

        $form->addEventEntry();
    }

    public function forceDeleteEvent(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeEventEntry($key);
    }

    public function addDegree(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->degreeRuleSet());

        $form->addDegreeEntry(
            $this->scientificDegreeLabel(data_get($form->degree, 'degree_and_name_id')),
            $this->educationDocumentLabel(data_get($form->degree, 'edu_doc_type_id'))
        );
    }

    public function forceDeleteDegree(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeDegreeEntry($key);
    }

    public function addElection(): void
    {
        $form = $this->miscFormInstance();

        $this->validate($this->electionRuleSet());

        $form->addElectionEntry();
    }

    public function forceDeleteElection(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeElectionEntry($key);
    }

    /**
     * @param  array<int, array>  $validators
     */
    protected function intersectValidators(array $validators): array
    {
        $filtered = array_values(array_filter($validators, fn ($rules) => ! empty($rules)));

        if (empty($filtered)) {
            return [];
        }

        return array_reduce(
            $filtered,
            fn ($carry, $rules) => $carry === null ? $rules : array_intersect_assoc($carry, $rules),
            null
        ) ?? [];
    }
}
