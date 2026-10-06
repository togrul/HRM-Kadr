@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $canManage = $this->canManage();
    $stats = collect($this->summaryStats)->keyBy('key');
    $stat = fn (string $key): int => (int) ($stats->get($key)['value'] ?? 0);

    // Bank and history are per-employee screens, so a global count on them would be a lie.
    $tabCounts = ['scales' => $stat('scales'), 'components' => $stat('components'), 'assignments' => $stat('assignments')];

    $addAction = match ($activeTab) {
        'scales' => ['scale', 'add_scale'],
        'components' => ['component', 'add_component'],
        'bank' => $selectedTabelNo ? ['bank', 'add_bank'] : null,
        'statutory' => ['statutory', 'add_rate'],
        default => null,
    };
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('compensation::dashboard.title')"
            :subtitle="__('compensation::dashboard.kicker')"
        >
            <x-context-panel.section>
                @foreach ($this->allowedTabsList as $tab)
                    <x-context-panel.item
                        wire:key="compensation-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                        :count="isset($tabCounts[$tab]) ? $num($tabCounts[$tab]) : null"
                    >{{ __('compensation::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :padded="false">
                <div class="p-2.5">
                    <x-context-panel.meta :items="collect($this->summaryStats)->map(fn ($item) => [
                        'label' => __('compensation::dashboard.summary.'.$item['key']),
                        'value' => $num($item['value']),
                        'dot' => $item['accent'],
                    ])->all()" />
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('compensation::dashboard.title')"
        :breadcrumb="__('compensation::dashboard.kicker')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($stat('scales'))" :label="__('compensation::dashboard.summary.scales')" />
            <x-page-header.stat :value="$num($stat('grades'))" :label="__('compensation::dashboard.summary.grades')" tone="violet" />
            <x-page-header.stat :value="$num($stat('assignments'))" :label="__('compensation::dashboard.summary.assignments')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button wire:click="switchTab('components')">
                {{ __('compensation::dashboard.actions.open_catalog') }}
            </x-pill-button>

            @if ($canManage && $addAction)
                <x-pill-button variant="primary" wire:click="$dispatch('compensation-open-panel', { panel: '{{ $addAction[0] }}' })">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('compensation::dashboard.actions.'.$addAction[1]) }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <div class="lg:hidden">
            <x-filter.nav>
                @foreach ($this->allowedTabsList as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __('compensation::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">

        {{-- ========= ASSIGNMENTS / BANK / HISTORY share the personnel picker ========= --}}
        @if (in_array($activeTab, ['assignments', 'bank', 'history'], true))
            <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                <p class="hrm-eyebrow">{{ __('compensation::dashboard.fields.personnel') }}</p>
                <div class="relative mt-2 max-w-xl" x-data="{ open: false }" x-on:click.outside="open = false">
                    @if ($selectedTabelNo)
                        <div class="flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                            <span class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $selectedPersonnelLabel }}</span>
                            <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">
                                {{ __('compensation::dashboard.actions.clear') }}
                            </button>
                        </div>
                    @else
                        <x-ui.input
                            icon="search"
                            wire:model.live.debounce.300ms="personnelSearch"
                            x-on:focus="open = true"
                            placeholder="{{ __('compensation::dashboard.actions.search_personnel') }}"
                        />
                        @if (count($this->personnelResults))
                            <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-card">
                                @foreach ($this->personnelResults as $result)
                                    <button
                                        type="button"
                                        wire:key="compensation-personnel-{{ $result['tabel_no'] }}"
                                        wire:click="selectPersonnel({{ \Illuminate\Support\Js::from($result['tabel_no']) }}, {{ \Illuminate\Support\Js::from($result['label']) }})"
                                        x-on:click="open = false"
                                        class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                                    >{{ $result['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </section>
        @endif

        @php
            $tabProps = match ($activeTab) {
                'scales' => ['scaleCount' => $stat('scales'), 'gradeCount' => $stat('grades')],
                'assignments', 'bank', 'history' => ['tabelNo' => $selectedTabelNo],
                default => [],
            };
        @endphp
        @livewire('compensation.tabs.'.$activeTab, $tabProps, key('compensation-tab-'.$activeTab))
    </div>
</div>
