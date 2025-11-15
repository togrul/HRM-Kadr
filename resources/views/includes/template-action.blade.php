<div class="sidemenu-title">
    <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
        {{ $title ?? ''}}
    </h2>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <div class="">
        <x-label for="template_data.id">{{ __('ID') }}</x-label>
        <x-livewire-input mode="gray" type="number" name="template_data.id" wire:model="template_data.id"></x-livewire-input>
        @error('template_data.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-ui.select-dropdown
            :label="__('Category')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="template_data.order_category_id"
            :model="$this->orderCategoryOptions"
        >
            <x-livewire-input
                mode="gray"
                name="searchCategory"
                wire:model.live="searchCategory"
                @click.stop="isOpen = true"
                x-on:input.stop="null"
                x-on:keyup.stop="null"
                x-on:keydown.stop="null"
                x-on:change.stop="null"
            ></x-livewire-input>
        </x-ui.select-dropdown>
        @error('template_data.order_category_id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <div class="">
        <x-label for="template_data.name">{{ __('Name') }}</x-label>
        <x-livewire-input mode="gray" name="template_data.name" wire:model="template_data.name"></x-livewire-input>
        @error('template_data.name')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="">
        <x-label for="template_data.order_model">{{ __('Model') }}</x-label>
        <x-livewire-input mode="gray" name="template_data.order_model" wire:model="template_data.order_model"></x-livewire-input>
        @error('template_data.order_model')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <div class="">
        <x-label for="template_data.blade">{{ __('Page') }}</x-label>
        <x-livewire-input mode="gray" name="template_data.blade" wire:model="template_data.blade"></x-livewire-input>
    </div>
    <div class="flex flex-col space-y-4 ">
        <x-label for="template_data.content">{{ __('File') }}</x-label>
        <div class="bg-gray-100 rounded-lg shadow-sm p-1">
            <div class="flex flex-col py-1" x-data="{ isUploading: false, progress: 0 }"
                 x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
                 x-on:livewire-upload-error="isUploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress">
                <div class="flex flex-col space-y-2 items-center">
                    <label
                        class="flex cursor-pointer bg-blue-100 py-2 px-3 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[40px]">
                              <span class="text-sm leading-normal">
                                <svg class="w-7 h-7" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"></path>
                                </svg>
                              </span>
                        <input type='file' class="hidden" wire:model="template_data.content"  />
                    </label>
                </div>
                <div x-show="isUploading">
                    <progress class="w-full rounded-lg overflow-hidden" max="100" x-bind:value="progress"></progress>
                </div>
            </div>

        </div>
        @error('template_data.content')
            <x-validation> {{ $message }} </x-validation>
        @enderror

        @if(array_key_exists('content',$template_data))
            @if(!is_string($template_data['content']))
                {{ $template_data['content']->getClientOriginalName() }}
            @else
                <a href="{{ \Illuminate\Support\Facades\Storage::url($template_data['content']) }}" target="_blank" class="text-blue-500 flex space-x-2 items-center">
                    <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
                    </svg>
                    <span class="text-base font-medium">{{ __('Download') }}</span>
                </a>
            @endif
        @endif
    </div>
</div>

<div class="flex justify-between items-end w-full">
    <x-modal-button>{{ __('Save') }}</x-modal-button>
</div>
