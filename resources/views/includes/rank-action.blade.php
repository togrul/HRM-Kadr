<div class="sidemenu-title">
    <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
        {{ $title ?? ''}}
    </h2>
</div>

<div
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="">
            <x-label for="form.id">{{ __('services::common.labels.id') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="form.id" wire:model="form.id"></x-livewire-input>
            @error('form.id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="sm:col-span-2">
            <x-ui.select-dropdown
                :label="__('services::common.labels.rank_category')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.rank_category_id"
                :model="$this->rankCategoryOptions"
            />
            @error('form.rank_category_id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="">
            <x-label for="form.name_az">{{ __('services::ranks.fields.name_az') }}</x-label>
            <x-livewire-input mode="gray" name="form.name_az" wire:model="form.name_az"></x-livewire-input>
            @error('form.name_az')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="">
            <x-label for="form.name_en">{{ __('services::ranks.fields.name_en') }}</x-label>
            <x-livewire-input mode="gray" name="form.name_en" wire:model="form.name_en"></x-livewire-input>
        </div>
        <div class="">
            <x-label for="form.name_ru">{{ __('services::ranks.fields.name_ru') }}</x-label>
            <x-livewire-input mode="gray" name="form.name_ru" wire:model="form.name_ru"></x-livewire-input>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <div class="">
            <x-label for="form.duration">{{ __('services::common.labels.duration') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="form.duration" wire:model="form.duration"></x-livewire-input>
        </div>
        <x-checkbox name="form.is_active" selected value="true" model="form.is_active">{{ __('services::common.labels.is_active_question') }}</x-checkbox>
    </div>
    <x-modal-button>{{ __('services::ranks.actions.save_rank') }}</x-modal-button>
</div>
