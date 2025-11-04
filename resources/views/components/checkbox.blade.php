@props([
    'name',
    'model',
    'value' => null,
    'hidden' => false,
    'checked' => false,
])

@php
    $extraClass = $hidden ? 'text-gray-400 line-through' : 'text-gray-700';
@endphp

<div class="flex items-center">
    <label class="relative inline-flex items-center cursor-pointer {{ $hidden ? 'line-through opacity-60' : '' }}">
        {{-- Real checkbox for Livewire binding --}}
        <input
            wire:model.live="{{ $model }}"
            @if($value) value="{{ $value }}" @endif
            name="{{ $name }}"
            type="checkbox"
            @if($checked) @checked(true) @endif
            {{ $hidden ? 'disabled' : '' }}
            class="peer w-5 h-5 mr-2 appearance-none rounded border border-neutral-300
                   bg-neutral-100 transition-colors duration-150
                   focus:outline-none focus:ring-2 focus:ring-green-500/40
                   checked:bg-green-500 checked:border-green-500
                   disabled:opacity-50"
        />

        {{-- Custom check icon --}}
        <svg
            class="absolute left-[0.25rem] top-[0.25rem] w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150 pointer-events-none"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 20 20"
        >
            <polyline points="5 10.5 8.5 14 15 6" />
        </svg>

        {{-- Label text --}}
        <span class="text-sm font-medium {{ $extraClass }}">
            {{ $slot }}
        </span>
    </label>
</div>
