<?php

namespace App\Livewire\Traits;

use App\Models\EducationalInstitution;
use Carbon\Carbon;

trait SelectListTrait
{
    public function setData($model, $key, $content, $name, $id, $multiple = null)
    {
        $this->setAttributes($model, $key, $content, $name, $id, $multiple);

        $this->updateExtraEducationData($model, $key, $id, $name);

        $this->clearSearchFields($content);
    }

    private function setAttributes($model, $key, $content, $name, $id, $multiple = null)
    {
        if (! empty($content)) {
            $this->{$content.'Id'} = $id;
            $this->{$content.'Name'} = $name;
        }

        $value = ['id' => $id, 'name' => $name];

        if (! is_null($multiple)) {
            data_set($this->{$model}, "{$multiple}.{$key}", $value);
        } else {
            data_set($this->{$model}, $key, $value);
        }

        $this->syncFormSelectValue($model, $key, $value, $multiple);
    }

    private function updateExtraEducationData($model, $key, $id, $name)
    {
        if (! empty($id)) {
            if ($model == 'extra_education' && $key == 'educational_institution_id') {
                $this->extra_education['name'] = $name;
                $this->extra_education['shortname'] = EducationalInstitution::find($id)->value('shortname');
            }
        } else {
            if ($model == 'extra_education' && $key == 'educational_institution_id') {
                unset($this->{$model}['name']);
                unset($this->{$model}['shortname']);
            }
        }
    }

    private function clearSearchFields($content)
    {
        $searchFields = match ($content) {
            'previousNationality','nationality' => ['searchPreviousNationality', 'searchNationality'],
            'extraInstitution','institution' => ['searchExtraInstitution', 'searchInstitution'],
            'extraEducationForm','educationForm' => ['searchExtraEducationForm', 'searchEducationForm'],
            'ranks' , 'militaryRank' => ['searchMilitaryRank', 'searchRank'],
            null => ['searchPersonnel'],
            default => []
        };

        if (isset($searchFields)) {
            foreach ($searchFields as $field) {
                $this->{$field} = '';
            }
        }
    }

    private function syncFormSelectValue(string $model, string $key, array $value, ?string $multiple = null): void
    {
        if ($model === 'ranks'
            && property_exists($this, 'laborActivityForm')
            && isset($this->laborActivityForm)) {
            $id = $value['id'] ?? null;
            data_set($this->laborActivityForm->rank, $key, $id);

            if (method_exists($this, 'syncArraysFromLaborActivityForm')) {
                $this->syncArraysFromLaborActivityForm();
            }

            return;
        }

        if ($model !== 'personnel' || ! property_exists($this, 'personalForm') || ! isset($this->personalForm)) {
            return;
        }

        if ($multiple !== null) {
            data_set($this->personalForm->personnel, "{$multiple}.{$key}", $value);
        } else {
            data_set($this->personalForm->personnel, $key, $value);
        }
    }

    protected function modifyArray($array, $_castedDates = null)
    {
        $filteredArray = array_filter($array, function ($key) {

            return stripos($key, '_id') !== false;

        }, ARRAY_FILTER_USE_KEY);

        foreach ($filteredArray as $key => $value) {
            unset($array[$key]);
            if (is_array($value)) {
                $array[$key] = $value['id'] ?? null;
            } else {
                $array[$key] = $value;
            }
        }

        if (! empty($_castedDates)) {
            foreach ($_castedDates as $_dates) {
                if (! empty($array[$_dates])) {
                    $array[$_dates] = Carbon::parse($array[$_dates])->format('Y-m-d');
                } else {
                    $array[$_dates] = null;
                }
            }
        }

        return $array;
    }
}
