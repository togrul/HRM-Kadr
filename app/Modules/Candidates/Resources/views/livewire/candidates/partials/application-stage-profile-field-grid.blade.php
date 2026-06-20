<div class="mt-4 grid gap-4 lg:grid-cols-2">
    @foreach ($this->profileFieldDefinitions as $field)
        <div class="flex flex-col">
            @if (($field['type'] ?? 'text') === 'select')
                <x-ui.select-dropdown
                    :label="__('candidates::recruitment.profile_fields.'.$field['key'])"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="form.profile_fields.{{ $field['key'] }}"
                    :model="collect($field['options'] ?? [])->map(fn ($option) => ['id' => $option, 'label' => __('candidates::recruitment.profile_field_options.'.$option)])->all()"
                />
            @elseif (($field['type'] ?? 'text') === 'date')
                <x-label for="form.profile_fields.{{ $field['key'] }}">{{ __('candidates::recruitment.profile_fields.'.$field['key']) }}</x-label>
                <x-pikaday-input mode="gray" name="form.profile_fields.{{ $field['key'] }}" format="Y-MM-DD" wire:model.live="form.profile_fields.{{ $field['key'] }}">
                    <x-slot name="script">
                        $el.onchange = function () { @this.set('form.profile_fields.{{ $field['key'] }}', $el.value); }
                    </x-slot>
                </x-pikaday-input>
            @else
                <x-label for="form.profile_fields.{{ $field['key'] }}">{{ __('candidates::recruitment.profile_fields.'.$field['key']) }}</x-label>
                <x-livewire-input mode="gray" type="{{ ($field['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="form.profile_fields.{{ $field['key'] }}" wire:model="form.profile_fields.{{ $field['key'] }}"></x-livewire-input>
            @endif
            @error('form.profile_fields.'.$field['key']) <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    @endforeach
</div>
