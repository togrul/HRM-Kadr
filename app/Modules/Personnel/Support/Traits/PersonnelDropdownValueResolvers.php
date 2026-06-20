<?php

namespace App\Modules\Personnel\Support\Traits;

trait PersonnelDropdownValueResolvers
{
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
}
