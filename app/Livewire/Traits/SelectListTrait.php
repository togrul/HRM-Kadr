<?php

namespace App\Livewire\Traits;

use Carbon\Carbon;

trait SelectListTrait
{
    public function setData($model, $key, $content, $name, $id, $multiple = null)
    {
        $this->setAttributes($model, $key, $content, $name, $id, $multiple);

        $this->clearSearchFields($content);
    }

    private function setAttributes($model, $key, $content, $name, $id, $multiple = null): void
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
    }

    private function clearSearchFields($content): void
    {
        if ($content !== null) {
            return;
        }

        if (property_exists($this, 'search') && isset($this->search)) {
            if (property_exists($this->search, 'personnel')) {
                $this->search->personnel = '';
            }
            if (property_exists($this->search, 'structure')) {
                $this->search->structure = '';
            }
            if (property_exists($this->search, 'position')) {
                $this->search->position = '';
            }
        }

        if (property_exists($this, 'searchPersonnel')) {
            $this->searchPersonnel = '';
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
