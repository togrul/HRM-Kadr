<?php

namespace App\Modules\Orders\Support\Traits\Validations;

use App\Models\Order;

trait OrderValidationTrait
{
    public function validationRules()
    {
        return [
            'main' => $this->mainValidationRules(),
            'dynamic' => $this->dynamicValidationRules(),
        ];
    }

    protected function mainValidationRules(): array
    {
        $rules = [
            'orderForm.order_type_id' => 'required|int|exists:order_types,id',
            'orderForm.order_no' => 'required|min:3|unique:order_logs,order_no'.(! empty($this->orderModel) ? ','.$this->orderModelData->id : ''),
            'orderForm.given_date' => 'required',
            'orderForm.status_id' => 'required|exists:order_statuses,id',
        ];

        if ($this->isForeignBusinessTrip()) {
            $rules['orderForm.description.description'] = 'required|min:2';
        }

        return $rules;
    }

    protected function dynamicValidationRules(): array
    {
        $rules = [
            'componentForms.*.component_id' => 'required|int|exists:components,id',
        ];

        return array_merge($rules, match ($this->selectedBlade) {
            Order::BLADE_DEFAULT => $this->defaultComponentRules(),
            Order::BLADE_VACATION => $this->vacationComponentRules(),
            Order::BLADE_BUSINESS_TRIP => $this->businessTripComponentRules(),
            default => [],
        });
    }

    protected function defaultComponentRules(): array
    {
        $fallback = [
            'componentForms.*.rank_id' => 'nullable|int|exists:ranks,id',
            'componentForms.*.personnel_id' => 'required|int',
            'componentForms.*.day' => 'required',
            'componentForms.*.month' => 'required|string|min:1',
            'componentForms.*.year' => 'required',
            'componentForms.*.structure_main_id' => 'required|int|exists:structures,id',
            'componentForms.*.structure_id' => 'required|int|exists:structures,id',
            'componentForms.*.position_id' => 'required|int|exists:positions,id',
            'componentForms.*.name' => 'required|string',
            'componentForms.*.surname' => 'required|string',
        ];

        return $this->metadataDrivenRulesForAllowedFields([
            'rank_id',
            'personnel_id',
            'day',
            'month',
            'year',
            'structure_main_id',
            'structure_id',
            'position_id',
            'name',
            'surname',
        ], $fallback);
    }

    protected function metadataDrivenRulesForAllowedFields(array $allowedFields, array $fallback): array
    {
        if (empty($this->templateRowFieldKeys) || empty($this->dynamicFieldCatalog)) {
            return $fallback;
        }

        $allowed = array_flip($allowedFields);
        $rules = [];

        foreach ($this->templateRowFieldKeys as $token) {
            $definition = $this->dynamicFieldCatalog[$token] ?? null;

            if (! is_array($definition)) {
                continue;
            }

            $field = (string) ($definition['field'] ?? '');
            if ($field === '' || $field === 'component_id') {
                continue;
            }

            $fieldRules = $definition['rules'] ?? null;
            if (is_array($fieldRules)) {
                $fieldRules = implode('|', array_filter($fieldRules));
            }

            $fieldKey = "componentForms.*.{$field}";
            $fallbackRule = $fallback[$fieldKey] ?? null;

            if (! is_string($fieldRules) || trim($fieldRules) === '') {
                $fieldRules = is_string($fallbackRule) ? $fallbackRule : null;
            }

            if (! isset($allowed[$field]) && (! is_string($fieldRules) || trim($fieldRules) === '')) {
                continue;
            }

            if (! is_string($fieldRules) || trim($fieldRules) === '') {
                continue;
            }

            $rules[$fieldKey] = trim($fieldRules);
        }

        if (! empty($rules)) {
            return $rules;
        }

        return $fallback;
    }

    protected function vacationComponentRules(): array
    {
        $fallback = [
            'componentForms.*.start_date' => 'required|date',
            'componentForms.*.end_date' => 'required|date|after:componentForms.*.start_date',
            'componentForms.*.days' => 'required|int|min:0',
        ];

        return $this->metadataDrivenRulesForAllowedFields([
            'start_date',
            'end_date',
            'days',
        ], $fallback);
    }

    protected function businessTripComponentRules(): array
    {
        $fallback = [
            'componentForms.*.start_date' => 'required|date',
            'componentForms.*.end_date' => 'required|date|after:componentForms.*.start_date',
            'componentForms.*.location' => 'required|string|min:2',
        ];

        if ($this->isInternalBusinessTrip()) {
            $fallback['componentForms.*.meeting_hour'] = 'required|string';
            $fallback['componentForms.*.return_month'] = 'required|string';
            $fallback['componentForms.*.return_day'] = 'required|int|min:1|max:31';
        }

        $allowed = ['start_date', 'end_date', 'location'];
        if ($this->isInternalBusinessTrip()) {
            $allowed = [...$allowed, 'meeting_hour', 'return_month', 'return_day'];
        }

        return $this->metadataDrivenRulesForAllowedFields($allowed, $fallback);
    }

    protected function validationAttributes()
    {
        return [
            'orderForm.order_type_id' => __('Template'),
            'orderForm.order_no' => __('Order number'),
            'orderForm.given_date' => __('Given date'),
            'orderForm.status_id' => __('Status'),
            'orderForm.description.description' => __('Description'),
            'componentForms.*.component_id' => __('Component'),
            'componentForms.*.personnel_id' => __('Personnel'),
            'componentForms.*.rank_id' => __('Rank'),
            'componentForms.*.day' => __('Day'),
            'componentForms.*.month' => __('Month'),
            'componentForms.*.year' => __('Year'),
            'componentForms.*.structure_main_id' => __('Main structure'),
            'componentForms.*.structure_id' => __('Structure'),
            'componentForms.*.position_id' => __('Position'),
            'componentForms.*.name' => __('Name'),
            'componentForms.*.surname' => __('Surname'),
            'componentForms.*.start_date' => __('Start Date'),
            'componentForms.*.end_date' => __('End Date'),
            'componentForms.*.days' => __('Days'),
            'componentForms.*.location' => __('Location'),
            'componentForms.*.meeting_hour' => __('Meeting hour'),
            'componentForms.*.return_month' => __('Return month'),
            'componentForms.*.return_day' => __('Return day'),
        ];
    }
}
