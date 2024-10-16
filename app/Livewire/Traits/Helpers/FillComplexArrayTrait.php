<?php

namespace App\Livewire\Traits\Helpers;

use Illuminate\Support\Str;

trait FillComplexArrayTrait
{
    protected function mapAttributes(array $attributes, array $getFrom, array $booleanColumns = []): array
    {
        $mappedAttributes = [];
        foreach ($attributes as $attribute) {
            $mappedAttributes[$attribute] = ! in_array($attribute, $booleanColumns)
                ? $getFrom[$attribute]
                : ($getFrom[$attribute] == 1 ? true : false);
        }

        return $mappedAttributes;
    }

    protected function handleRelatedEntity(
        string $entity,
        string $field,
        string $fillTo,
        array $getFrom,
        string $titleField = 'title',
        array $extraOptions = [],
        ?string $differentSelectInput = null,
        bool $hasSelectedField = true,
        bool $hasLocale = false
    ) {
        $title = $hasLocale ? $titleField.'_'.config('app.locale') : $titleField;
        if (! empty($getFrom[$field])) {
            $this->{$fillTo}[$field] = [
                'id' => $getFrom[$entity]['id'],
                $titleField => $getFrom[$entity][$title],
            ];

            if ($hasSelectedField) {
                $convertedFieldName = Str::camel($differentSelectInput ?? $entity);
                // Assign variables for easier access later
                $this->{$convertedFieldName.'Id'} = $getFrom[$entity]['id'];
                $this->{$convertedFieldName.'Name'} = $getFrom[$entity][$title];

                // Handle any extra fields or flags
                if (! empty($extraOptions)) {
                    if (! empty($extraOptions['extra_field'])) {
                        $fillTo[$extraOptions['extra_field']] = $getFrom[$extraOptions['extra_field']];
                    }

                    if (! empty($extraOptions['flag'])) {
                        $this->{$extraOptions['flag']} = true;
                    }
                }
            }
        }
    }

    protected function handleRelatedEntitiesMultiDimensional(
        string $entity,
        string $field,
        string $key,
        string $fillTo,
        array $getFrom,
        string $titleField = 'title',
        bool $hasLocale = false
    ) {
        $title = $hasLocale ? $titleField.'_'.config('app.locale') : $titleField;
        if (! empty($getFrom[$field])) {
            $this->{$fillTo}[$key][$field] = [
                'id' => $getFrom[$entity]['id'],
                $titleField => $getFrom[$entity][$title],
            ];
        }
    }
}
