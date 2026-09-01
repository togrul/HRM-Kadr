{{--
    Search + status chips + (when more than one workflow pack is installed) pack chips.
    Rendered inside x-page-header's default slot, so every recruitment list shares it.
--}}
<div class="flex flex-col gap-2.5">
    <div class="flex flex-wrap items-center gap-3">
        <label class="relative w-full sm:max-w-[360px]">
            <span class="sr-only">{{ __('candidates::common.labels.search') }}</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $searchPlaceholder ?? __('candidates::common.labels.search') }}"
                class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
            />
        </label>

        @if (! empty($statusOptions))
            <x-filter.nav wrap class="min-w-0">
                <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                    {{ __('candidates::common.labels.all') }}
                </x-filter.item>
                @foreach ($statusOptions as $statusOption)
                    <x-filter.item wire:click.prevent="setStatus('{{ $statusOption }}')" :active="$status === $statusOption">
                        {{ __('candidates::recruitment.statuses.'.$statusOption) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        @endif
    </div>

    @if ($this->recruitmentPackSelectorVisible())
        <x-filter.nav wrap class="min-w-0">
            <x-filter.item wire:click.prevent="setPack('all')" :active="$pack === 'all'">
                {{ __('candidates::common.labels.all') }}
            </x-filter.item>
            @foreach ($this->recruitmentAvailablePacks() as $packOption)
                <x-filter.item wire:click.prevent="setPack('{{ $packOption }}')" :active="$pack === $packOption">
                    {{ __('candidates::recruitment.packs.'.$packOption) }}
                </x-filter.item>
            @endforeach
        </x-filter.nav>
    @endif
</div>
