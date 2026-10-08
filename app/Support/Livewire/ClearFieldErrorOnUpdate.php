<?php

namespace App\Support\Livewire;

use Livewire\ComponentHook;

/**
 * Drops a field's validation error as soon as the user changes that field.
 *
 * Registered once for every Livewire component (AppServiceProvider), so a corrected
 * field never keeps showing its old red message until the next submit. It runs before
 * the component's own updated*() hooks: a component that re-validates on update
 * (validateOnly) still puts a fresh error back when the new value is wrong too.
 *
 * A component that must keep its errors across updates implements
 * KeepsValidationErrorsOnUpdate.
 */
class ClearFieldErrorOnUpdate extends ComponentHook
{
    public function update(string $propertyName, string $fullPath, mixed $newValue): void
    {
        if ($this->component instanceof KeepsValidationErrorsOnUpdate) {
            return;
        }

        $bag = $this->component->getErrorBag();
        if ($bag->isEmpty()) {
            return;
        }

        $keys = array_values(array_filter(
            $bag->keys(),
            fn (string $key): bool => $key === $fullPath || str_starts_with($key, $fullPath.'.'),
        ));

        if ($keys !== []) {
            $this->component->resetErrorBag($keys);
        }
    }
}
