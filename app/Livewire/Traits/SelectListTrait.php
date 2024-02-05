<?php

namespace App\Livewire\Traits;

trait SelectListTrait
{
    public function setData($model,$key,$content,$name,$id)
    {
        $this->setAttributes($model, $key, $content, $name, $id);

        $this->updateExtraEducationData($model, $key, $id, $name);

        $this->clearSearchFields($content);
    }

    private function setAttributes($model, $key, $content, $name, $id)
    {
        $this->{$content . 'Id'} = $id;
        $this->{$content . 'Name'} = $name;

        if (array_key_exists($key, $this->{$model})) {
            $this->{$model}[$key] = ['id' => $id, 'name' => $name];
        } else {
            $this->{$model} += [$key => ['id' => $id, 'name' => $name]];
        }
    }

    private function updateExtraEducationData($model, $key, $id, $name)
    {
        if (!empty($id)) {
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
        $searchFields = match ($content)
        {
            'previousNationality','nationality' => ['searchPreviousNationality', 'searchNationality'],
            'extraInstitution','institution' => ['searchExtraInstitution', 'searchInstitution'],
            'extraEducationForm','educationForm' => ['searchExtraEducationForm', 'searchEducationForm'],
            'ranks' , 'militaryRank' => ['searchMilitaryRank', 'searchRank'],
            default => []
        };

        if (isset($searchFields)) {
            foreach ($searchFields as $field) {
                $this->{$field} = '';
            }
        }
    }

    protected function modifyArray($array)
    {
        $filteredArray = array_filter($array, function($key) {

            return stripos($key, "_id") !== false;

        }, ARRAY_FILTER_USE_KEY);

        foreach($filteredArray as $key => $value)
        {
            unset($array[$key]);
            $array[$key] = $value['id'];
        }

        return $array;
    }
}
