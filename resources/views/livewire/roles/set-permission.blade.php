<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {!! $title ?? '' !!}
        </h2>
    </div>

<div 
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">

        <div class="col-span-3 px-2 py-2 border-2 rounded-lg">
            <div class="flex flex-row items-center h-8">
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

        @foreach($permissions as $key => $permission)
        <div class="flex flex-col space-y-1" wire:key="{{ $permission->id }}">
            @php
                $prefix = explode('-',$permission->name)[0];
            @endphp
            <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                <div class="flex flex-row items-center h-8">
                    <div class="flex items-center">
                        <label class="label">
                            <input wire:model="permissionList" value="{{ $permission->id }}"
                                   id="{{$permission->id }}"
                                   type="checkbox" class="label__checkbox"  />
                            <span class="label__text">
                                <span class="label__check">
                                    <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </span>
                            </span>
                        </label>
                    </div>
                    <div class="text-xs">
                        <label for="permission_{{$permission->id }}"
                               class="font-medium text-gray-700 flex flex-col">
                            <span> {{$permission->name}}</span>
                            <span class="text-blue-500">{{__($prefix.'_permission')}}</span>
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