@php
    $stats = $this->stats;
    $distribution = $this->scoreDistribution;

    $metrics = [
        ['key' => 'cycles', 'value' => $stats['cycles'], 'dot' => 'bg-[#059669]'],
        ['key' => 'templates', 'value' => $stats['templates'], 'dot' => 'bg-[#0284c7]'],
        ['key' => 'sections', 'value' => $stats['sections'], 'dot' => 'bg-[#7c3aed]'],
        ['key' => 'items', 'value' => $stats['items'], 'dot' => 'bg-[#a1a1aa]'],
        ['key' => 'forms', 'value' => $stats['forms'], 'dot' => 'bg-[#f59e0b]'],
        ['key' => 'scores', 'value' => $distribution['average'], 'dot' => 'bg-[#e11d48]'],
        ['key' => 'links', 'value' => $stats['links'], 'dot' => 'bg-[#0369a1]'],
    ];

    $bucketBar = [
        'high' => 'bg-[#059669]',
        'medium' => 'bg-[#f59e0b]',
        'weak' => 'bg-[#e11d48]',
    ];
    $bucketRange = ['high' => '85+', 'medium' => '60–84', 'weak' => '<60'];

    $categoryTone = fn (?string $category): string => match ($category) {
        'high' => 'bg-emerald-50 text-emerald-700',
        'medium' => 'bg-amber-50 text-amber-700',
        'weak' => 'bg-rose-50 text-rose-700',
        default => 'bg-[#f4f4f5] text-ink-muted',
    };
    $pill = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium';
@endphp

<div class="flex flex-col gap-4">
    {{-- metric tiles sit straight on the page: wrapping them in a card nested a card in a card --}}
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <div class="rounded-2xl border border-hairline bg-white px-4 py-3.5 shadow-card">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[12.5px] font-medium text-ink-muted">{{ __('performance_evaluation::dashboard.stats.'.$metric['key']) }}</span>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $metric['dot'] }}"></span>
                    </div>
                    <p class="hrm-num mt-1.5 text-[22px] font-semibold tracking-[-0.03em] text-ink">{{ $metric['value'] }}</p>
                </div>
            @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        {{-- ===================== score distribution ===================== --}}
        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('performance_evaluation::dashboard.panel.score_distribution') }}</h2>
                <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint">
                    {{ __('performance_evaluation::dashboard.panel.score_distribution_note', ['count' => $distribution['total'], 'average' => $distribution['average']]) }}
                </span>
            </div>

            <div class="space-y-3 p-4">
                @if ($distribution['total'] === 0)
                    <x-ui.empty-state icon="icons.performance-icon" :message="__('performance_evaluation::dashboard.panel.no_scores')" />
                @else
                    @foreach ($distribution['buckets'] as $bucket)
                        <div wire:key="performance-bucket-{{ $bucket['key'] }}">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-[12.5px] text-ink-soft">
                                    {{ __('performance_evaluation::dashboard.categories.'.$bucket['key']) }}
                                    <span class="hrm-num text-ink-faint">({{ $bucketRange[$bucket['key']] }})</span>
                                </span>
                                <span class="hrm-num text-[13px] font-semibold text-ink">{{ $bucket['count'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full {{ $bucketBar[$bucket['key']] }}" style="width: {{ $bucket['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        {{-- ===================== weak area integration ===================== --}}
        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('performance_evaluation::dashboard.cards.weak_links') }}</h2>
                <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('performance_evaluation::dashboard.panel.weak_area_note') }}</p>
            </div>

            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentWeakLinks as $link)
                    <div wire:key="performance-weak-link-{{ $link->id }}" class="flex items-center gap-3 px-4 py-2.5">
                        <span @class([
                            'hrm-num flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px] text-[12.5px] font-semibold',
                            'bg-[#ffe4e6] text-[#be123c]' => $link->form?->final_category === 'weak',
                            'bg-[#fef3c7] text-[#b45309]' => $link->form?->final_category === 'medium',
                            'bg-[#d1fae5] text-[#047857]' => $link->form?->final_category === 'high',
                            'bg-[#f4f4f5] text-[#52525b]' => $link->form?->final_category === null,
                        ])>{{ $link->form?->final_score !== null ? (int) $link->form->final_score : '—' }}</span>

                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">
                                {{ $link->competency?->name ?? __('performance_evaluation::dashboard.labels.no_competency') }}
                            </p>
                            <p class="truncate text-[11.5px] text-ink-faint">
                                {{ $link->form?->personnel?->fullname ?? '—' }}
                                <span class="px-0.5">·</span>
                                {{ $link->trainingNeed?->presentedReason() }}
                            </p>
                        </div>

                        <span class="{{ $pill }} {{ $categoryTone($link->form?->final_category) }}">
                            {{ __('performance_evaluation::dashboard.labels.linked_need') }}
                        </span>
                    </div>
                @empty
                    <div class="p-3">
                        <x-ui.empty-state icon="icons.link-icon" :message="__('performance_evaluation::dashboard.empty.weak_links')" />
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ===================== recent records ===================== --}}
    <div class="grid gap-4 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <p class="hrm-eyebrow">{{ __('performance_evaluation::dashboard.cards.recent_cycles') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentCycles as $cycle)
                    <div wire:key="performance-cycle-{{ $cycle->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $cycle->name }}</p>
                            <p class="hrm-num truncate text-[11.5px] text-ink-faint">
                                {{ __('performance_evaluation::dashboard.cycle_types.'.$cycle->cycle_type) }}
                                <span class="px-0.5">·</span>
                                {{ $cycle->period_start?->format('d.m.Y') }} – {{ $cycle->period_end?->format('d.m.Y') }}
                            </p>
                        </div>
                        <span class="{{ $pill }} {{ $cycle->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $cycle->status === 'active' ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                            {{ __('performance_evaluation::dashboard.statuses.'.$cycle->status) }}
                        </span>
                    </div>
                @empty
                    <div class="p-3">
                        <x-ui.empty-state icon="icons.clock-icon" :message="__('performance_evaluation::dashboard.empty.recent_cycles')" />
                    </div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <p class="hrm-eyebrow">{{ __('performance_evaluation::dashboard.cards.recent_templates') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentTemplates as $template)
                    <div wire:key="performance-template-{{ $template->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $template->name }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">
                                {{ $template->code ?: __('performance_evaluation::dashboard.labels.no_code') }}
                                <span class="px-0.5">·</span>
                                {{ __('performance_evaluation::dashboard.labels.sections_count', ['count' => $template->sections_count]) }}
                            </p>
                        </div>
                        <span class="{{ $pill }} {{ $template->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $template->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                            {{ $template->is_active ? __('performance_evaluation::dashboard.statuses.active') : __('performance_evaluation::dashboard.labels.inactive') }}
                        </span>
                    </div>
                @empty
                    <div class="p-3">
                        <x-ui.empty-state icon="icons.folder-plus-icon" :message="__('performance_evaluation::dashboard.empty.recent_templates')" />
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ===================== evaluator + exports ===================== --}}
    <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <p class="hrm-eyebrow">{{ __('performance_evaluation::dashboard.cards.evaluator_workspace') }}</p>
            </div>
            <div class="space-y-3 p-4">
                <p class="text-[12.5px] leading-relaxed text-ink-muted">{{ __('performance_evaluation::dashboard.labels.evaluator_workspace_hint') }}</p>
                <x-pill-button :href="route('performance-evaluation.evaluator', ['return' => route('performance-evaluation', ['tab' => 'overview'])])">
                    {{ __('performance_evaluation::dashboard.actions.open_evaluator_workspace') }}
                </x-pill-button>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="border-b border-hairline-subtle bg-[#fafafa] px-4 py-3">
                <p class="hrm-eyebrow">{{ __('performance_evaluation::dashboard.cards.reports') }}</p>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="exportPerformanceFormsReport" wire:loading.attr="disabled" wire:target="exportPerformanceFormsReport" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink"><svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>{{ __('performance_evaluation::dashboard.actions.export_forms_report') }}</button>
                    <button type="button" wire:click="exportPerformanceSummaryReport" wire:loading.attr="disabled" wire:target="exportPerformanceSummaryReport" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink"><svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>{{ __('performance_evaluation::dashboard.actions.export_summary_report') }}</button>
                    <button type="button" wire:click="exportPerformanceWeakLinksReport" wire:loading.attr="disabled" wire:target="exportPerformanceWeakLinksReport" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink"><svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>{{ __('performance_evaluation::dashboard.actions.export_weak_links_report') }}</button>
                    <button type="button" wire:click="exportPerformanceWeakPivotReport" wire:loading.attr="disabled" wire:target="exportPerformanceWeakPivotReport" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink"><svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>{{ __('performance_evaluation::dashboard.actions.export_weak_pivot_report') }}</button>
                    <button type="button" wire:click="exportPerformanceAuditReport" wire:loading.attr="disabled" wire:target="exportPerformanceAuditReport" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-3 text-[14px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink"><svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M12 11v6M9 14l3 3 3-3"/></svg>{{ __('performance_evaluation::dashboard.actions.export_audit_report') }}</button>
                </div>
                <p class="mt-3 text-[11.5px] leading-relaxed text-ink-faint">{{ __('performance_evaluation::dashboard.labels.export_report_hint') }}</p>
            </div>
        </section>
    </div>
</div>
