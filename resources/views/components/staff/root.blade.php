@props([
  'title', 
  'structureId', 
  'hasParent' => false, 
  'total_sum' => 0, 
  'total_filled' => 0, 
  'total_vacant' => 0,
  'canEditStaff' => false,
  'canDeleteStaff' => false,
])

<div @class([
    'rounded-lg px-1 py-1 flex flex-col gap-2 shadow-sm bg-neutral-300/20',
])>
    <div class="flex items-center justify-between px-2 py-1">
        <h1 class="text-base font-medium !text-zinc-900/80 flex flex-col items-start">
            {{ $title }}
        </h1>
        <div class="flex items-center space-x-3">
            @if ($hasParent)
            <div class="flex gap-2 divide-x divide-zinc-300 items-center">
                <div class="flex items-center gap-2">
                  <span class="text-zinc-400 font-semibold text-xs uppercase tracking-tighter">{{ __('Total') }}:</span>
                  <span class="text-blue-500 font-semibold text-[13px] relative top-[-1px]">{{ $total_sum }}</span>
                </div>
                <div class="flex items-center gap-2 px-2">
                  <span class="text-zinc-400 font-semibold text-xs uppercase tracking-tighter">{{ __('Filled') }}:</span>
                  <span class="text-rose-500 font-semibold text-[13px] relative top-[-1px]">{{ $total_filled }}</span>
                </div>
                <div class="flex items-center gap-2 px-2">
                  <span class="text-zinc-400 font-semibold text-xs uppercase tracking-tighter">{{ __('Vacant') }}:</span>
                  <span class="text-green-500 font-semibold text-[13px] relative top-[-1px]">{{ $total_vacant }}</span>
                </div>
            </div>
            @endif
            <div class="flex items-center space-x-2">
              @if ($canEditStaff)
                  <button wire:click="openSideMenu('edit-staff',{{ $structureId }})"
                      wire:loading.attr="disabled"
                      wire:target="openSideMenu"
                      type="button"
                      class="flex items-center justify-center w-8 h-8 transition-all duration-300 rounded-lg appearance-none bg-white/80 hover:bg-white/60">
                      @include('components.icons.edit-icon', [
                          'color' => 'text-zinc-500',
                          'hover' => 'text-zinc-600',
                      ])
                  </button>
              @endif
              @if ($canDeleteStaff)
                  <button wire:click.prevent="setDeleteStaff({{ $structureId }})"
                      wire:loading.attr="disabled"
                      wire:target="setDeleteStaff"
                      type="button"
                      class="flex items-center justify-center w-8 h-8 transition-all duration-300 rounded-lg appearance-none bg-white/80 hover:bg-white/60">
                      @include('components.icons.delete-icon', [
                          'color' => 'text-rose-400',
                          'hover' => 'text-rose-300',
                      ])
                  </button>
              @endif
          </div>
        </div>
    </div>

    {{-- body --}}
    <div class="flex flex-col gap-2">
        {{ $slot }}
    </div>
</div>
