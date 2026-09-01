@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $score = (int) ($summary['compliance_score'] ?? 0);

    $statusFacets = [
        'expired' => 'bg-[#f43f5e]',
        'expiring_30' => 'bg-[#f59e0b]',
        'expiring_60' => 'bg-[#0ea5e9]',
        'valid' => 'bg-[#10b981]',
        'missing' => 'bg-[#a1a1aa]',
    ];

    $metrics = [
        ['key' => 'total', 'tone' => 'ink'],
        ['key' => 'expired', 'tone' => 'rose'],
        ['key' => 'expiring_30', 'tone' => 'amber'],
        ['key' => 'expiring_60', 'tone' => 'blue'],
        ['key' => 'valid', 'tone' => 'green'],
        ['key' => 'missing', 'tone' => 'ink'],
    ];

    $statusTone = fn (string $status): string => match ($status) {
        'expired', 'missing' => 'rose',
        'expiring_30' => 'amber',
        'expiring_60' => 'sky',
        default => 'green',
    };
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('compliance::documents.kicker')"
            :subtitle="__('compliance::documents.labels.document_count', ['count' => $num($summary['total'] ?? 0)])"
        >
            <x-context-panel.section>
                <x-context-panel.item
                    wire:click.prevent="$set('status', '')"
                    :active="$status === ''"
                    :count="$num($summary['total'] ?? 0)"
                >{{ __('compliance::documents.filters.all_statuses') }}</x-context-panel.item>

                @foreach ($statusFacets as $option => $dot)
                    <x-context-panel.item
                        wire:key="compliance-status-{{ $option }}"
                        wire:click.prevent="$set('status', '{{ $option }}')"
                        :active="$status === $option"
                        :dot="$dot"
                        :count="$num($summary[$option] ?? 0)"
                    >{{ __('compliance::documents.status.'.$option) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :title="__('compliance::documents.fields.document_type')">
                <x-context-panel.item wire:click.prevent="$set('type', '')" :active="$type === ''">
                    {{ __('compliance::documents.filters.all_types') }}
                </x-context-panel.item>
                @foreach ($typeCounts as $option => $count)
                    <x-context-panel.item
                        wire:key="compliance-type-{{ $option }}"
                        wire:click.prevent="$set('type', '{{ $option }}')"
                        :active="$type === $option"
                        :count="$num($count)"
                    >{{ __('compliance::documents.types.'.$option) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :title="__('compliance::documents.summary.compliance_score')" :padded="false">
                <div class="px-3.5 pb-3.5 pt-1">
                    <p class="hrm-num text-[22px] font-semibold leading-none tracking-[-0.035em] text-ink">
                        {{ $score }}<span class="ml-1 text-[12px] font-medium text-ink-faint">/ 100</span>
                    </p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                        <div @class([
                            'h-full rounded-full',
                            'bg-[#059669]' => $score >= 85,
                            'bg-[#d97706]' => $score >= 60 && $score < 85,
                            'bg-[#e11d48]' => $score < 60,
                        ]) style="width: {{ max(0, min(100, $score)) }}%"></div>
                    </div>
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('compliance::documents.title')"
        :breadcrumb="__('compliance::documents.kicker')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">
                {{ __('compliance::documents.actions.reset_filters') }}
            </x-pill-button>

            <x-pill-button variant="emerald" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv">
                {{ __('compliance::documents.actions.export_csv') }}
            </x-pill-button>
        </x-slot:actions>

        <p class="max-w-3xl text-[13px] leading-6 text-ink-muted">{{ __('compliance::documents.description') }}</p>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($metrics as $metric)
                <x-ui.metric-tile
                    wire:key="compliance-metric-{{ $metric['key'] }}"
                    :label="__('compliance::documents.summary.'.$metric['key'])"
                    :value="$num($summary[$metric['key']] ?? 0)"
                    :tone="$metric['tone']"
                />
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="w-full sm:max-w-[380px]">
                    <x-ui.input
                        icon="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('compliance::documents.placeholders.search') }}"
                    />
                </div>
                <p class="hrm-num shrink-0 text-[11.5px] text-ink-faint">
                    {{ __('compliance::documents.labels.result_count', ['count' => $num($rows->count())]) }}
                </p>
            </div>

            <x-table.tbl :headers="[
                __('compliance::documents.columns.employee'),
                __('compliance::documents.columns.document'),
                __('compliance::documents.columns.expires_at'),
                __('compliance::documents.columns.days_left'),
                __('compliance::documents.columns.status'),
            ]">
                @forelse ($rows as $row)
                    <tr wire:key="compliance-row-{{ $loop->index }}">
                        <x-table.td standart-width>
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="(string) $row['personnel_name']" :tone="in_array($row['status'], ['expired', 'missing'], true) ? 'rose' : 'neutral'" />
                                <div class="min-w-0 max-w-[240px] leading-tight">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $row['personnel_name'] }}</p>
                                    <p class="truncate text-[11px] text-ink-faint">{{ $row['structure_name'] }} <span class="px-0.5">›</span> {{ $row['position_name'] }}</p>
                                </div>
                            </div>
                        </x-table.td>

                        <x-table.td standart-width>
                            <p class="max-w-[200px] truncate text-[13px] text-ink-soft">{{ $row['document_label'] }}</p>
                            <p class="hrm-num max-w-[200px] truncate text-[11px] text-ink-faint">{{ $row['document_number'] }}</p>
                        </x-table.td>

                        <x-table.td>
                            <span class="hrm-num text-[13px] text-ink-soft">{{ $row['expires_at'] }}</span>
                        </x-table.td>

                        <x-table.td>
                            <span class="hrm-num text-[13px] text-ink-muted">{{ $row['days_left'] ?? '—' }}</span>
                        </x-table.td>

                        <x-table.td>
                            <x-small-badge :mode="$statusTone($row['status'])" dot>
                                {{ __('compliance::documents.status.'.$row['status']) }}
                            </x-small-badge>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10">
                            <x-ui.empty-state icon="icons.document-icon" :message="__('compliance::documents.labels.result_count', ['count' => 0])" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </section>

        @if ($structureScores->isNotEmpty())
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compliance::documents.sections.structure_scores') }}</h2>
                </div>
                <div class="space-y-3 px-4 py-3.5">
                    @foreach ($structureScores as $structureScore)
                        <div wire:key="compliance-structure-{{ $loop->index }}">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="min-w-0 truncate text-[12.5px] text-ink-soft">{{ $structureScore['structure_name'] }}</span>
                                <span class="hrm-num shrink-0 text-[13px] font-semibold text-ink">{{ $structureScore['score'] }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-[#059669]' => $structureScore['score'] >= 85,
                                    'bg-[#d97706]' => $structureScore['score'] >= 60 && $structureScore['score'] < 85,
                                    'bg-[#e11d48]' => $structureScore['score'] < 60,
                                ]) style="width: {{ max(3, min(100, (int) $structureScore['score'])) }}%"></div>
                            </div>
                            <p class="mt-1 text-[11px] text-ink-faint">
                                {{ __('compliance::documents.labels.risk_summary', ['missing' => $structureScore['missing'], 'expired' => $structureScore['expired']]) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
