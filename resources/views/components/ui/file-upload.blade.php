@props(['model', 'data'])

@php
    [$modelName, $modelKey] = explode('.', $model);
@endphp

<div class="p-1 rounded-lg shadow-sm bg-neutral-100">
    <div class="flex flex-col py-1" x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
        x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress">
        <div class="flex flex-col items-center space-y-2">
            <label
                class="flex cursor-pointer bg-neutral-200/80 py-2 px-3 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[40px]">
                <span class="text-sm leading-normal">
                    <svg class="w-7 h-7" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z">
                        </path>
                    </svg>
                </span>
                <input type='file' class="hidden" wire:model="{{ $model }}" />
            </label>

        </div>
        <div x-show="isUploading">
            <progress class="w-full overflow-hidden rounded-lg" max="100" x-bind:value="progress"></progress>
        </div>
    </div>
    @if ($data)
        @php
            $filename = is_string($data) ? basename($data) : $data->getClientOriginalName();
        @endphp
        <div class="px-2">
            <a
                class="inline-flex max-w-full text-sm text-indigo-600 hover:underline break-all"
                href="{{ is_string($data) ? \Illuminate\Support\Facades\Storage::url($data) : '#' }}"
                target="_blank"
                rel="noreferrer"
                title="{{ $filename }}"
            >
                <span class="inline-block max-w-full truncate">{{ $filename }}</span>
            </a>
        </div>
    @endif
</div>
