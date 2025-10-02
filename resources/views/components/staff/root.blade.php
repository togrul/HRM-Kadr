@props(['title', 'structureId', 'hasParent' => false, 'total_sum' => 0, 'total_filled' => 0, 'total_vacant' => 0])

<div @class([
    'rounded-lg px-4 py-3 flex flex-col space-y-2 shadow-sm bg-slate-300/20',
])>
    <div class="flex items-center justify-between px-4 py-2">
        <h1 class="text-lg font-medium !text-zinc-900/80 flex flex-col space-y-1 items-start">
            {!! $title !!}</h1>
        <div class="flex space-x-2 items-center">
            @can('edit-staff')
                <button wire:click="openSideMenu('edit-staff',{{ $structureId }})"
                    class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-white/80 transition-all duration-300 hover:bg-white/60">
                    @include('components.icons.edit-icon', [
                        'color' => 'text-zinc-500',
                        'hover' => 'text-zinc-600',
                    ])
                </button>
            @endcan
            @can('delete-staff')
                <button wire:click.prevent="setDeleteStaff({{ $structureId }})"
                    class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-white/80 transition-all duration-300 hover:bg-white/60">
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
        <div class="md:col-span-2 flex flex-col space-y-2">
            {{ $slot }}
        </div>
        @if ($hasParent)
            <div class="px-6 flex flex-col space-y-2">
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total count') }}</span>
                    <span class="text-blue-600 text-xl font-medium">{{ $total_sum }}</span>
                </div>
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total filled') }}</span>
                    <span class="text-rose-500 text-xl font-medium">{{ $total_filled }}</span>
                </div>
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500">{{ __('Total vacant') }}</span>
                    <span class="text-green-500 text-xl font-medium">{{ $total_vacant }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
