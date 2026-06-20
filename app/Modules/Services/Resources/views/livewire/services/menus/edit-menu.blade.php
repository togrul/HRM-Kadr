<div>
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {{ $title ?? ''}}
          </h2>
    </div>

    <div
        class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    >
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="">
                <x-label for="menu.name">{{ __('services::common.labels.name') }}</x-label>
                <x-livewire-input mode="gray" name="menu.name" wire:model="menu.name"></x-livewire-input>
                @error('menu.name')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="">
                <x-label for="menu.color">{{ __('services::common.labels.color') }}</x-label>
                <x-livewire-input mode="gray" name="menu.color" wire:model="menu.color"></x-livewire-input>
                @error('menu.color')
                  <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="">
                <x-label for="menu.order">{{ __('services::common.labels.order_no') }}</x-label>
                <x-livewire-input type="number" mode="gray" name="menu.order" wire:model="menu.order"></x-livewire-input>
                @error('menu.order')
                  <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="">
                <x-label for="menu.url">{{ __('services::common.labels.url') }}</x-label>
                <x-livewire-input mode="gray" name="menu.url" wire:model="menu.url"></x-livewire-input>
                @error('menu.url')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="">
                <x-label for="menu.icon">{{ __('services::common.labels.icon') }}</x-label>
                <x-textarea mode="gray" name="menu.icon" wire:model="menu.icon" placeholder="{{ __('services::common.labels.icon') }}"></x-textarea>
                @error('menu.icon')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    :label="__('services::common.labels.permissions')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="menu.permission_id"
                    :model="$this->permissionOptions"
                    search-model="search.permission"
                >
                </x-ui.select-dropdown>
                @error('menu.permission_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div>
                <x-checkbox
                    model="menu.is_active"
                    value=""
                    name="isActive"
                >
                    {{ __('services::common.labels.active') }}
                </x-checkbox>

            </div>
        </div>
        <x-modal-button>{{ __('services::menus.actions.save_menu') }}</x-modal-button>
    </div>
</div>
