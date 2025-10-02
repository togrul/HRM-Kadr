@props([
    'title',
    'checkbox' => null,
    'checkboxTitle' => null
])

<div class="flex flex-col bg-white border-2 border-gray-200 rounded-md space-y-2 shadow-sm overflow-hidden">
    <div class="border-b border-slate-300 bg-zinc-100 px-3 py-2 flex items-center space-x-3">
        <h1 class="text-lg font-medium">{{ __($title) }}</h1>
        @if($checkbox)
            <x-checkbox name="{{ $checkbox }}" model="{{ $checkbox }}">{{ __($checkboxTitle) }}</x-checkbox>
        @endif
    </div>
    <div class="px-3 py-2 flex flex-col space-y-3">
        {{ $slot }}
    </div>
</div>
