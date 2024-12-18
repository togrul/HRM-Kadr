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
                <x-label for="menu.name">{{ __('Name') }}</x-label>
                <x-livewire-input mode="gray" name="menu.name" wire:model="menu.name"></x-livewire-input>
                @error('menu.name')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="">
                <x-label for="menu.color">{{ __('Color') }}</x-label>
                <x-livewire-input mode="gray" name="menu.color" wire:model="menu.color"></x-livewire-input>
                @error('menu.color')
                  <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="">
                <x-label for="menu.order">{{ __('Order no') }}</x-label>
                <x-livewire-input type="number" mode="gray" name="menu.order" wire:model="menu.order"></x-livewire-input>
                @error('menu.order')
                  <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="">
                <x-label for="menu.url">{{ __('URL') }}</x-label>
                <x-livewire-input mode="gray" name="menu.url" wire:model="menu.url"></x-livewire-input>
                @error('menu.url')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="">
                <x-label for="menu.icon">{{ __('Icon') }}</x-label>
                <x-textarea mode="gray" name="menu.icon" wire:model="menu.icon" placeholder="{{ __('Icon') }}"></x-textarea>
                @error('menu.icon')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="flex flex-col">
                @php
                    $selectedName = array_key_exists('permission_id', $menu) ? $menu['permission_id']['name'] : '---';
                    $selectedId = array_key_exists('permission_id', $menu) ? $menu['permission_id']['id'] : -1;
                @endphp
                <x-select-list class="w-full" :title="__('Permissions')" mode="gray" :selected="$selectedName" name="permissionId">
                    <x-select-list-item wire:click="setData('menu','permission_id',null,'---',null)" :selected="'---' == $selectedName"
                                        wire:model='menu.permission_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach($permissions as $permission)
                        <x-select-list-item wire:click="setData('menu','permission_id',null,'{{ $permission->name }}',{{ $permission->id }})"
                                            :selected="$permission->id === $selectedId" wire:model='menu.permission_id.id'>
                            {{ $permission->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('menu.permission_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div>
                <x-checkbox
                    model="menu.is_active"
                    value=""
                    name="isActive"
                >
                    {{ __('Active') }}
                </x-checkbox>

            </div>
        </div>
        <x-modal-button>{{ __('Save menu') }}</x-modal-button>
    </div>
</div>
