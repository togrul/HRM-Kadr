<?php

namespace App\Modules\Orders\Support\Traits\Orders;

use Carbon\Carbon;
use Illuminate\Support\Collection;

trait HandlesOrderComponentFieldState
{
    public function updated($propertyName, $value)
    {
        if ($this->handleComponentPropertyMutation($propertyName, $value)) {
            return;
        }
    }

    protected function optionsFromCollection(Collection $collection, callable $labelResolver): array
    {
        return $collection
            ->map(fn ($item) => [
                'id' => (int) data_get($item, 'id'),
                'label' => (string) $labelResolver($item),
            ])
            ->unique('id')
            ->values()
            ->all();
    }

    protected function normalizeVacancyEntries(array $entries): array
    {
        return collect($entries)
            ->values()
            ->map(function ($entry, $idx) {
                $structureId = $this->valueAsInt($entry, 'structure_id');
                $positionId = $this->valueAsInt($entry, 'position_id');
                $personnelId = $this->valueAsInt($entry, 'personnel_id')
                    ?? $this->valueAsInt($this->componentForms[$idx] ?? [], 'personnel_id');
                $componentId = $this->valueAsInt($entry, 'component_id')
                    ?? $this->valueAsInt($this->componentForms[$idx] ?? [], 'component_id');

                if (! $structureId || ! $positionId || ! $componentId) {
                    return null;
                }

                return [
                    'structure_id' => $structureId,
                    'position_id' => $positionId,
                    'personnel_id' => $personnelId,
                    'component_id' => $componentId,
                    'structure_label' => $this->dropdownFieldLabel('structure_id', $structureId),
                    'position_label' => $this->dropdownFieldLabel('position_id', $positionId),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function valueAsInt($entry, string $field): ?int
    {
        $value = data_get($entry, $field);

        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return $value !== null ? (int) $value : null;
    }

    protected function personnelOptionLabel($model): string
    {
        $parts = array_filter([
            data_get($model, 'surname'),
            data_get($model, 'name'),
            data_get($model, 'patronymic'),
        ]);

        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return (string) data_get($model, 'fullname', '');
    }

    protected function attributeValuePayload(string $field, $value, ?int $row = null)
    {
        if (! $this->isDropdownField($field)) {
            return $value;
        }

        $id = $value !== null ? (int) $value : null;

        return [
            'id' => $id,
            'name' => $id ? $this->dropdownFieldLabel($field, $id, $row) : '---',
        ];
    }

    public function componentFieldLabel(int $row, string $field): string
    {
        $value = data_get($this->componentForms[$row] ?? [], $field);

        if (is_array($value) && array_key_exists('name', $value)) {
            return (string) $value['name'];
        }

        if ($this->isDropdownField($field)) {
            $resolvedValue = is_array($value) ? ($value['id'] ?? null) : $value;

            return $this->dropdownFieldLabel($field, $resolvedValue, $row);
        }

        return $value ?: '---';
    }

    public function componentFieldValue(int $row, string $field)
    {
        $value = data_get($this->componentForms[$row] ?? [], $field);

        if (is_array($value)) {
            return $value['id'] ?? null;
        }

        return $value;
    }

    protected function handleComponentPropertyMutation(string $propertyName, $value): bool
    {
        if (! str_starts_with($propertyName, 'componentForms.')) {
            return false;
        }

        $segments = explode('.', $propertyName);
        if (count($segments) < 3) {
            return false;
        }

        [, $rowIndex, $field] = $segments;
        $row = is_numeric($rowIndex) ? (int) $rowIndex : null;

        if ($row !== null && $this->isDropdownField($field)) {
            $value = $value !== null ? (int) $value : null;
            $this->componentForms[$row][$field] = $value;
        }

        if ($field === 'component_id') {
            $this->componentSelected($value, $row);

            return true;
        }

        if ($field === 'personnel_id' && $row !== null) {
            $this->updatePersonnelName($value, $row);
        }

        if ($field === 'structure_main_id' && $row !== null) {
            $this->coded_list[$row] = (int) $value === 1;
            $this->componentForms[$row]['structure_id'] = null;
            unset($this->componentForms[$row]['structure'], $this->componentForms[$row]['structure_name']);
        }

        if (in_array($field, ['start_date', 'end_date'], true) && $row !== null) {
            if (! empty($this->componentForms[$row]['start_date']) && ! empty($this->componentForms[$row]['end_date'])) {
                $start_dt = Carbon::createFromDate($this->componentForms[$row]['start_date']);
                $end_dt = Carbon::createFromDate($this->componentForms[$row]['end_date']);
                $this->componentForms[$row]['days'] = $start_dt->diffInDays($end_dt);
            }
        }

        return true;
    }
}
