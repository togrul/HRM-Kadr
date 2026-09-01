@php
    $panelCard = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3';
    $emptyBox = 'rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint';
    $tile = 'rounded-xl border border-hairline bg-[#fafafa] px-3 py-2.5';
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.analytics'),
            'panelSubtitle' => $this->recruitmentPackLabel($this->currentPack),
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('candidates::recruitment.titles.analytics')"
        :breadcrumb="__('candidates::common.titles.candidates')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 15 3-4 3 3 5-7"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($this->currentPack) }}</x-small-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        {{-- summary tiles --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($this->summaryCards as $card)
                <div class="rounded-2xl border border-hairline bg-white px-4 py-3 shadow-card">
                    <p class="hrm-eyebrow">{{ $card['label'] }}</p>
                    <p class="hrm-num mt-1.5 text-[21px] font-semibold tracking-[-0.03em] text-ink">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            {{-- pipeline by stage --}}
            <section class="{{ $panelCard }}">
                <div class="{{ $sectionHead }}">
                    <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.pipeline_summary') }}</h2>
                </div>
                <div class="grid gap-2 p-3 sm:grid-cols-2">
                    @foreach ($this->stageSummary as $stage)
                        <div class="{{ $tile }}">
                            <p class="truncate text-[12px] font-medium text-ink-soft">{{ $stage['label'] }}</p>
                            <p class="hrm-num mt-1 text-[19px] font-semibold tracking-[-0.03em] text-ink">{{ $stage['count'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- time to stage --}}
            <section class="{{ $panelCard }}">
                <div class="{{ $sectionHead }}">
                    <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.time_to_stage') }}</h2>
                </div>
                <div class="space-y-2 p-3">
                    @forelse ($this->timeToStageSummary as $row)
                        <div class="flex items-center justify-between gap-3 {{ $tile }}">
                            <div class="min-w-0">
                                <p class="truncate text-[12.5px] font-medium text-ink">{{ $row['label'] }}</p>
                                <p class="hrm-num text-[11px] text-ink-faint">{{ $row['total'] }} {{ __('candidates::recruitment.labels.applications') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="hrm-num text-[16px] font-semibold text-ink">{{ $row['avg_days'] }}</p>
                                <p class="text-[10.5px] text-ink-faint">{{ __('candidates::recruitment.labels.days_avg') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="{{ $emptyBox }}">{{ __('candidates::recruitment.empty.analytics_stage_velocity') }}</p>
                    @endforelse
                </div>
            </section>

            {{-- source effectiveness --}}
            <section class="{{ $panelCard }}">
                <div class="{{ $sectionHead }}">
                    <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.source_effectiveness') }}</h2>
                </div>
                <div class="space-y-2 p-3">
                    @forelse ($this->sourceEffectivenessSummary as $row)
                        <div class="{{ $tile }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 truncate text-[12.5px] font-medium text-ink">{{ $row['label'] }}</p>
                                <x-small-badge :mode="$row['success_rate'] >= 50 ? 'green' : 'secondary'">{{ $row['success_rate'] }}%</x-small-badge>
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <div class="rounded-lg border border-hairline bg-white px-2 py-1.5">
                                    <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.total') }}</p>
                                    <p class="hrm-num mt-0.5 text-[14px] font-semibold text-ink">{{ $row['total'] }}</p>
                                </div>
                                <div class="rounded-lg border border-hairline bg-white px-2 py-1.5">
                                    <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.successful') }}</p>
                                    <p class="hrm-num mt-0.5 text-[14px] font-semibold text-[#047857]">{{ $row['successful'] }}</p>
                                </div>
                                <div class="rounded-lg border border-hairline bg-white px-2 py-1.5">
                                    <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.rejected') }}</p>
                                    <p class="hrm-num mt-0.5 text-[14px] font-semibold text-[#be123c]">{{ $row['rejected'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="{{ $emptyBox }}">{{ __('candidates::recruitment.empty.analytics_sources') }}</p>
                    @endforelse
                </div>
            </section>

            {{-- rejection reasons --}}
            <section class="{{ $panelCard }}">
                <div class="{{ $sectionHead }}">
                    <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.rejection_reasons') }}</h2>
                </div>
                <div class="space-y-2 p-3">
                    @forelse ($this->rejectionReasonSummary as $row)
                        <div class="flex items-center justify-between gap-3 {{ $tile }}">
                            <p class="min-w-0 truncate text-[12.5px] font-medium text-ink-soft">{{ $row['label'] }}</p>
                            <p class="hrm-num shrink-0 text-[16px] font-semibold text-[#be123c]">{{ $row['count'] }}</p>
                        </div>
                    @empty
                        <p class="{{ $emptyBox }}">{{ __('candidates::recruitment.empty.analytics_rejection_reasons') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- recent activity --}}
        <section class="{{ $panelCard }}">
            <div class="{{ $sectionHead }}">
                <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.recent_activity') }}</h2>
            </div>
            <div class="grid gap-2 p-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($this->recentMoves as $event)
                    <article class="{{ $tile }}">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge mode="secondary">{{ __('candidates::recruitment.stages.'.$event->stage_key) }}</x-small-badge>
                            <span class="text-[10.5px] text-ink-faint">{{ $event->action }}</span>
                        </div>
                        <p class="mt-2 truncate text-[12.5px] font-semibold text-ink">{{ $event->application?->candidate?->fullname ?? '—' }}</p>
                        <p class="truncate text-[11.5px] text-ink-muted">{{ $event->application?->opening?->title ?? '—' }}</p>
                        <p class="mt-1 truncate text-[10.5px] text-ink-faint">
                            {{ $event->actor?->name ?? '—' }} · <span class="hrm-num">{{ optional($event->occurred_at)->format('d.m.Y H:i') ?? '—' }}</span>
                        </p>
                    </article>
                @empty
                    <p class="{{ $emptyBox }} md:col-span-2 xl:col-span-3">{{ __('candidates::recruitment.empty.analytics_activity') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
