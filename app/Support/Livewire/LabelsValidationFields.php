<?php

namespace App\Support\Livewire;

/**
 * Turns [validation path => field key] into translated attribute labels, so validation
 * messages read "Ad" instead of "scale form.name".
 */
trait LabelsValidationFields
{
    /**
     * Translation prefix the field keys are appended to, e.g. "payroll::dashboard.fields.".
     */
    abstract protected function fieldLabelPrefix(): string;

    /**
     * @param  array<string,string>  $map
     * @return array<string,string>
     */
    protected function fieldLabels(array $map): array
    {
        return array_map(fn (string $key): string => __($this->fieldLabelPrefix().$key), $map);
    }
}
