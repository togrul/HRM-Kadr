@props(['title', 'structureId', 'hasParent' => false, 'total_sum' => 0, 'total_filled' => 0, 'total_vacant' => 0])

<div @class([
    'rounded-lg px-4 py-3 flex flex-col space-y-2 shadow-sm bg-neutral-300/20',
])>
    <div class="flex items-center justify-between px-4 py-2">
        <h1 class="text-lg font-medium !text-zinc-900/80 flex flex-col space-y-1 items-start">
            {!! $title !!}</h1>
        <div class="flex items-center space-x-2">
            @can('edit-staff')
                <button wire:click="openSideMenu('edit-staff',{{ $structureId }})"
                    class="flex items-center justify-center w-8 h-8 transition-all duration-300 rounded-lg appearance-none bg-white/80 hover:bg-white/60">
                    @include('components.icons.edit-icon', [
                        'color' => 'text-zinc-500',
                        'hover' => 'text-zinc-600',
                    ])
                </button>
            @endcan
            @can('delete-staff')
                <button wire:click.prevent="setDeleteStaff({{ $structureId }})"
                    class="flex items-center justify-center w-8 h-8 transition-all duration-300 rounded-lg appearance-none bg-white/80 hover:bg-white/60">
                    @include('components.icons.delete-icon', [
                        'color' => 'text-rose-400',
                        'hover' => 'text-rose-300',
                    ])
                </button>
            @endcan
        </div>
    </div>

    {{-- body --}}
    <div @class([
        'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 divide-x divide-zinc-300/60' => $hasParent,
    ])>
        <div class="flex flex-col space-y-2 md:col-span-2">
            {{ $slot }}
        </div>
        @if ($hasParent)
            <div class="flex flex-col px-6 space-y-2">
                <div class="flex flex-col px-3 py-2 rounded-lg shadow-sm bg-white/90">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total count') }}</span>
                    <span class="text-xl font-medium text-blue-600">{{ $total_sum }}</span>
                </div>
                <div class="flex flex-col px-3 py-2 rounded-lg shadow-sm bg-white/90">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total filled') }}</span>
                    <span class="text-xl font-medium text-rose-500">{{ $total_filled }}</span>
                </div>
                <div class="flex flex-col px-3 py-2 rounded-lg shadow-sm bg-white/90">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total vacant') }}</span>
                    <span class="text-xl font-medium text-green-500">{{ $total_vacant }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
