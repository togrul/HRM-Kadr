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

        if (! is_null($multiple)) {
            if (array_key_exists($multiple, $this->{$model})) {
                if (array_key_exists($key, $this->{$model}[$multiple])) {
                    $this->{$model}[$multiple][$key] = ['id' => $id, 'name' => $name];
                }
            } else {
                $this->{$model} += [$multiple => [$key => ['id' => $id, 'name' => $name]]];
            }
        } else {
            if (array_key_exists($key, $this->{$model})) {
                $this->{$model}[$key] = ['id' => $id, 'name' => $name];
            } else {
                $this->{$model} += [$key => ['id' => $id, 'name' => $name]];
            }
        }
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

    protected function modifyArray($array, $_castedDates = null)
    {
        $filteredArray = array_filter($array, function ($key) {

            return stripos($key, '_id') !== false;

        }, ARRAY_FILTER_USE_KEY);

        foreach ($filteredArray as $key => $value) {
            unset($array[$key]);
            $array[$key] = $value['id'];
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
