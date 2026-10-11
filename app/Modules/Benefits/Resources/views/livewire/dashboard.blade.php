@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $canManage = $this->canManage();
    $stats = collect($this->summaryStats)->keyBy('key');
    $stat = fn (string $key): int => (int) ($stats->get($key)['value'] ?? 0);
    $tabCounts = ['plans' => $stat('plans'), 'enrollments' => $stat('enrollments'), 'renewals' => $stat('open_windows')];
    $addAction = match ($activeTab) {
        'plans' => ['plan', 'add_plan'],
        'enrollments' => ['enroll', 'add_enrollment'],
        default => null,
    };
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('benefits::dashboard.title')"
            :subtitle="__('benefits::dashboard.kicker')"
        >
            <x-context-panel.section>
                @foreach ($this->allowedTabsList as $tab)
                    <x-context-panel.item
                        wire:key="benefits-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                        :count="isset($tabCounts[$tab]) ? $num($tabCounts[$tab]) : null"
                    >{{ __('benefits::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :padded="false">
                <div class="p-2.5">
                    <x-context-panel.meta :items="collect($this->summaryStats)->map(fn ($item) => [
                        'label' => __('benefits::dashboard.summary.'.$item['key']),
                        'value' => $num($item['value']),
                        'dot' => $item['accent'],
                    ])->all()" />
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('benefits::dashboard.title')"
        :breadcrumb="__('benefits::dashboard.tabs.'.$activeTab)"
        :breadcrumb-root="__('benefits::dashboard.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($stat('plans'))" :label="__('benefits::dashboard.summary.plans')" />
            <x-page-header.stat :value="$num($stat('enrollments'))" :label="__('benefits::dashboard.summary.enrollments')" tone="green" />
            <x-page-header.stat :value="$num($stat('open_windows'))" :label="__('benefits::dashboard.summary.open_windows')" tone="violet" />
        </x-slot:stats>

        <x-slot:actions>
            @if ($canManage && $addAction)
                <x-pill-button variant="primary" wire:click="$dispatch('benefits-open-panel', { panel: '{{ $addAction[0] }}' })">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('benefits::dashboard.actions.'.$addAction[1]) }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <div class="lg:hidden">
            <x-filter.nav>
                @foreach ($this->allowedTabsList as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __('benefits::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        @livewire('benefits.tabs.'.$activeTab, [], key('benefits-tab-'.$activeTab))
    </div>
</div>
