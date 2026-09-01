@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $counts = $this->panelCounts;
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::common.titles.candidates'),
            'panelSubtitle' => $num($counts['active_candidates']).' '.__('candidates::recruitment.labels.active_candidates'),
            'panelOpenings' => $this->openOpenings,
            'panelCounts' => $counts,
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('candidates::recruitment.titles.pipeline')"
        :breadcrumb="__('candidates::common.titles.candidates')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($this->totalApplications)" :label="__('candidates::recruitment.labels.applications')" />
            <x-page-header.stat :value="$num($this->totalOpenings)" :label="__('candidates::recruitment.titles.openings')" tone="blue" />
            <x-page-header.stat :value="$num($this->hiredCount)" :label="__('candidates::recruitment.labels.hired_label')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button :href="route('candidates.openings')" wire:navigate>
                {{ __('candidates::recruitment.actions.open_openings') }}
            </x-pill-button>
            <x-pill-button variant="primary" :href="route('candidates')" wire:navigate>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('candidates::common.titles.candidates') }}
            </x-pill-button>
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex flex-wrap items-center gap-3">
                <label class="relative w-full sm:max-w-[360px]">
                    <span class="sr-only">{{ __('candidates::recruitment.labels.search_placeholder') }}</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('candidates::recruitment.labels.search_placeholder') }}"
                        class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                    />
                </label>

                <x-filter.nav wrap class="min-w-0">
                    <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('candidates::common.labels.all') }}
                    </x-filter.item>
                    @foreach (['active', 'closed', 'rejected', 'withdrawn'] as $statusOption)
                        <x-filter.item wire:click.prevent="setStatus('{{ $statusOption }}')" :active="$status === $statusOption">
                            {{ __('candidates::recruitment.statuses.'.$statusOption) }}
                        </x-filter.item>
                    @endforeach
                </x-filter.nav>
            </div>

            @if ($this->currentOpening || $this->currentCandidate || $this->recruitmentPackSelectorVisible())
                <div class="flex flex-wrap items-center gap-2">
                    @if ($this->currentOpening)
                        <x-small-badge mode="blue" as="button" wire:click="setOpening('all')">
                            {{ $this->currentOpening->title }} ✕
                        </x-small-badge>
                    @endif
                    @if ($this->currentCandidate)
                        <x-small-badge mode="green" as="button" wire:click="setCandidate('all')">
                            {{ $this->currentCandidate->fullname }} ✕
                        </x-small-badge>
                    @endif
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
            @endif

            <div class="flex flex-wrap items-center gap-2">
                @if ($stage !== 'all')
                    <x-small-badge mode="secondary" as="button" wire:click="setStage('all')">
                        {{ $this->recruitmentStageLabel($stage) }} &times;
                    </x-small-badge>
                @endif
                <p class="text-[11.5px] text-ink-faint">{{ __('candidates::recruitment.labels.board_hint') }}</p>
            </div>
        </div>
    </x-page-header>

    {{-- ===================== stage board ===================== --}}
    <div class="hrm-scroll overflow-x-auto px-4 py-4 sm:px-5">
        <div class="flex min-w-max items-start gap-3">
            @foreach ($this->stageBoard as $column)
                <section wire:key="pipeline-column-{{ $column['key'] }}"
                    class="flex w-[268px] shrink-0 flex-col rounded-2xl border border-hairline bg-[#fafafa]">
                    <header class="flex items-center gap-2 px-3 py-2.5">
                        <span @class([
                            'h-1.5 w-1.5 shrink-0 rounded-full',
                            'bg-[#f43f5e]' => $column['terminal'] ?? false,
                            'bg-[#0ea5e9]' => ! ($column['terminal'] ?? false),
                        ])></span>
                        <button type="button" wire:click.prevent="setStage('{{ $column['key'] }}')"
                            class="min-w-0 flex-1 truncate text-left text-[12.5px] font-semibold text-ink transition hover:underline">
                            {{ $column['label'] }}
                        </button>
                        <span class="hrm-num shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[10.5px] text-ink-muted">{{ $num($column['count']) }}</span>
                    </header>

                    <div class="flex flex-col gap-2 px-2 pb-2">
                        @forelse ($column['cards'] as $application)
                            @php
                                $candidate = $application->candidate;
                                $fullname = (string) ($candidate?->fullname ?? '—');
                                $age = $candidate?->birthdate ? \Carbon\Carbon::parse($candidate->birthdate)->age : null;
                            @endphp
                            <a
                                wire:key="pipeline-card-{{ $application->id }}"
                                href="{{ route('candidates.applications.show', $application) }}"
                                wire:navigate
                                class="block rounded-xl border border-hairline bg-white px-3 py-2.5 transition hover:border-zinc-300 hover:shadow-card"
                            >
                                <div class="flex items-start gap-2.5">
                                    <x-avatar :name="$fullname" size="sm" />
                                    <div class="min-w-0 flex-1 leading-tight">
                                        <p class="truncate text-[13px] font-semibold text-ink">{{ $fullname }}</p>
                                        @if ($age !== null)
                                            <p class="hrm-num mt-0.5 text-[11px] text-ink-faint">{{ __('candidates::recruitment.labels.age_short', ['count' => $age]) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <p class="mt-2 truncate text-[11.5px] text-ink-muted">{{ $application->opening?->title ?? '—' }}</p>

                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    @if ($application->source?->name)
                                        <x-small-badge mode="secondary">{{ $application->source->name }}</x-small-badge>
                                    @endif
                                    @if ($application->applied_at)
                                        <span class="hrm-num text-[10.5px] text-ink-faint">{{ $application->applied_at->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="px-1 py-3 text-center text-[11.5px] text-ink-faint">—</p>
                        @endforelse

                        @if ($column['hidden'] > 0)
                            <button type="button" wire:click.prevent="setStage('{{ $column['key'] }}')"
                                class="rounded-xl border border-dashed border-hairline px-3 py-2 text-[11.5px] font-medium text-ink-muted transition hover:border-zinc-300 hover:text-ink">
                                {{ __('candidates::recruitment.labels.hidden_cards', ['count' => $column['hidden']]) }}
                            </button>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
