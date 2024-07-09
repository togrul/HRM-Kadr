<div class="sidemenu-title">
    <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
        {{ $title ?? ''}}
    </h2>
</div>

<div
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
        <div class="">
            <x-label for="form.id">{{ __('ID') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="form.id" wire:model="form.id"></x-livewire-input>
            @error('form.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="">
            <x-label for="form.name_az">{{ __('Name') }} AZ</x-label>
            <x-livewire-input mode="gray" name="form.name_az" wire:model="form.name_az"></x-livewire-input>
            @error('form.name_az')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="">
            <x-label for="form.name_en">{{ __('Name') }} EN</x-label>
            <x-livewire-input mode="gray" name="form.name_en" wire:model="form.name_en"></x-livewire-input>
        </div>
        <div class="">
            <x-label for="form.name_ru">{{ __('Name') }} RU</x-label>
            <x-livewire-input mode="gray" name="form.name_ru" wire:model="form.name_ru"></x-livewire-input>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div class="">
            <x-label for="form.duration">{{ __('Duration') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="form.duration" wire:model="form.duration"></x-livewire-input>
        </div>
        <x-checkbox name="form.is_active" selected value="true" model="form.is_active">{{ __('Is active?') }}</x-checkbox>
    </div>
    <x-modal-button>{{ __('Save rank') }}</x-modal-button>
</div>
