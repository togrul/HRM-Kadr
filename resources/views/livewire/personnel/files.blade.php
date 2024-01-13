<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="flex flex-col space-y-2 py-3 bg-slate-50 px-2 rounded-lg border">
        <h1 class="text-xl font-medium text-gray-900">{{ __('New file') }}</h1>
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col space-y-4 ">
                <div class="bg-slate-100 rounded-lg shadow-sm p-1">
                    <div class="flex flex-col py-1" x-data="{ isUploading: false, progress: 0 }"
                         x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
                         x-on:livewire-upload-error="isUploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <div class="flex flex-col space-y-2 items-center">
                            <label
                                class="flex cursor-pointer bg-slate-200 py-2 px-3 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[40px]">
                          <span class="text-sm leading-normal">
                            <svg class="w-7 h-7" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"></path>
                            </svg>
                          </span>
                                <input type='file' class="hidden" wire:model="files.file"  />
                            </label>
                        </div>
                        <div x-show="isUploading">
                            <progress class="w-full rounded-lg overflow-hidden" max="100" x-bind:value="progress"></progress>
                        </div>
                    </div>

                </div>
                @error('files.file') <x-validation> {{ $message }} </x-validation> @enderror
            </div>

            <div class="flex flex-col">
                <x-label for="files.filename">{{ __('File name') }}</x-label>
                <x-livewire-input mode="gray"  name="files.filename" wire:model.live="files.filename"></x-livewire-input>
                @error('files.filename') <x-validation> {{ $message }} </x-validation> @enderror
            </div>

        </div>
        <x-button mode="black" class="w-max" wire:click="addFile">{{ __('Add') }}</x-button>
    </div>

    <div class="flex flex-col space-y-2">
        @foreach($file_list as $key => $file)
            @php
                $route = is_string($file['file']) ? "/storage/{$file['file']}" : $file['file']->temporaryUrl();
            @endphp
            <div class="rounded-lg bg-neutral-100 px-4 py-2 shadow-sm flex justify-between items-center">

                <div class="flex space-x-4 items-center justify-start">
                    <span class="font-semibold text-gray-500">{{ $key + 1 }}</span>
                     <span class="p-2 rounded-lg bg-teal-50 w-10 h-10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-500" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
                        </svg>
                    </span>
                    <a target="_blank" class="text-blue-500 font-medium" href="{{ $route }}">{{ $file['filename'] }}</a>
                </div>
                <button
                    onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                    wire:click="deleteFile({{ $key }})"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>


    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
    </div>
</div>
