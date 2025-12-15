<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {!! $title ?? '' !!}
        </h2>
    </div>

<div
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    x-data="{ activeTab: 'sections' }"
>
    <div class="flex items-center w-full px-1 py-1 space-x-2 rounded-lg tabs bg-gray-50">
        <button
            class="flex items-center justify-center px-3 py-1 text-sm text-gray-500 uppercase transition-all duration-300 appearance-none tab"
            :class="{ 'active border-b-2 border-blue-500 text-gray-900': activeTab === 'sections' }"
            @click="activeTab = 'sections'">
            {{ __('Sections') }}
        </button>
        <button
            class="flex items-center justify-center px-3 py-1 text-sm text-gray-500 uppercase transition-all duration-300 appearance-none tab"
            :class="{ 'active border-b-2 border-blue-500 text-gray-900': activeTab === 'structures' }"
            @click="activeTab = 'structures'">
            {{ __('Structures') }}
        </button>
    </div>

    <div x-show="activeTab == 'sections'"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="grid grid-cols-1 gap-2 sm:grid-cols-3">

        <div class="col-span-3 px-2 py-2 border-2 rounded-lg">
            <div class="flex flex-row items-center">
                <div class="flex items-center">
                    <label class="label">
                        <input wire:model.live="selectAll" type="checkbox" class="label__checkbox"  />
                        <span class="label__text">
                             <span class="label__check">
                                  <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                              </span>
                        </span>
                    </label>
                </div>
                <div class="text-sm">
                    <label for="selectAll"
                           class="font-medium text-gray-500">
                         {{__('Select all')}}
                    </label>
                </div>
            </div>
        </div>

        @foreach($permissions as $keyData => $permissionData)
            <div class="flex flex-col space-y-1" wire:key="key-{{ $keyData }}">
                <h2 class="text-sm font-medium text-blue-600 underline uppercase">{{ __($keyData) }}</h2>
                @foreach($permissionData as $key => $permission)
                    <div class="flex flex-col space-y-1" wire:key="{{ $permission['id'] }}">
                        <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                            <div class="flex flex-row items-center space-x-1">
                                <div class="flex items-center">
                                    <label class="label">
                                        <input wire:model="permissionList" value="{{ $permission['id'] }}"
                                               id="{{$permission['id'] }}"
                                               type="checkbox" class="label__checkbox"  />
                                        <span class="label__text">
                                            <span class="label__check">
                                                <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <div class="text-xs">
                                    <label for="permission_{{$permission['id'] }}"
                                           class="flex flex-col font-medium leading-tight text-gray-700 break-words"
                                    >
                                        <span class="text-xs font-semibold uppercase">{{ __($permission['title']) }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @endforeach
    </div>

    <div x-show="activeTab == 'structures'"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="grid grid-cols-1 gap-2 sm:grid-cols-3"
    >
        <div class="col-span-3 px-2 py-2 border-2 rounded-lg">
            <div class="flex flex-row items-center">
                <div class="flex items-center">
                    <label class="label">
                        <input wire:model.live="selectAllStructure" type="checkbox" class="label__checkbox"  />
                        <span class="label__text">
                             <span class="label__check">
                                  <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                              </span>
                        </span>
                    </label>
                </div>
                <div class="text-sm">
                    <label for="selectAllStructure"
                           class="font-medium text-gray-500">
                        {{__('Select all')}}
                    </label>
                </div>
            </div>
        </div>

        @foreach($structures as $key => $structure)
            <div class="flex flex-col space-y-0" wire:key="{{ $structure->id }}_{{ $structure->shortname }}">
                <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                    <div class="flex flex-row items-center space-x-1">
                        <div class="flex items-center">
                            <label class="label">
                                <input wire:model="permissionStructureList"
                                       value="{{ (int) $structure->id }}"
                                       wire:change="updatePermissionStructureList({{ $structure->id }})"
                                       id="permission_{{ $structure->id }}_{{ $structure->shortname }}"
                                       type="checkbox"
                                       class="label__checkbox"
                                />
                                <span class="label__text">
                                    <span class="label__check">
                                        <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <div class="text-sm">
                            <label for="permission_{{ $structure->code }}_{{ $structure->id }}"
                                   class="flex flex-col font-medium leading-tight text-gray-700 break-words whitespace-normal">
                                <span> {{ $structure->name }}</span>
                                <span class="text-xs text-blue-500">{{ $structure->shortname }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>

  <x-modal-button>{{ __('Save permission') }}</x-modal-button>
</div>
