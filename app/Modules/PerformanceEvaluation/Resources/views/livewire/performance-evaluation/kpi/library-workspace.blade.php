@php
    $t = 'performance_evaluation::kpi';
    $statusBadge = [
        'active' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'draft' => ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'],
        'archived' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
    ];
    $directionTone = ['higher_better' => 'text-emerald-600', 'lower_better' => 'text-sky-600', 'range' => 'text-violet-600'];
    $input = 'h-10 w-full rounded-xl border border-hairline bg-[#fafafa] px-3 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none';
    $iconButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300';
    $kpiColumns = 'md:grid md:grid-cols-[minmax(0,2.4fr)_minmax(0,1.3fr)_minmax(0,1.1fr)_56px_104px_104px] md:items-center md:gap-4';
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-4">
    {{-- ───────────── section switch + actions ───────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-filter.nav>
            <x-filter.item wire:click="$set('section', 'kpis')" :active="$section === 'kpis'">
                {{ __($t.'.sections.kpis') }}
                <span class="hrm-num ml-1 opacity-60">{{ $this->kpis->count() }}</span>
            </x-filter.item>
            <x-filter.item wire:click="$set('section', 'templates')" :active="$section === 'templates'">
                {{ __($t.'.sections.templates') }}
                <span class="hrm-num ml-1 opacity-60">{{ $this->templates->count() }}</span>
            </x-filter.item>
            @can('manage-performance-evaluation')
                <x-filter.item wire:click="$set('section', 'notifications')" :active="$section === 'notifications'">
                    {{ __($t.'.sections.notifications') }}
                </x-filter.item>
            @endcan
        </x-filter.nav>

        @if ($section === 'notifications')
            <div class="min-w-[9rem]">
                <x-ui.filter-native-select wire:model.live="templateLocale">
                    @foreach (config('app.locales', ['az']) as $locale)
                        <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </div>
        @endif

        @can('manage-performance-evaluation')
            @if ($section !== 'notifications')
            <x-pill-button variant="primary" wire:click="{{ $section === 'kpis' ? 'openKpiForm' : 'openTemplateForm' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                {{ $section === 'kpis' ? __($t.'.actions.add_kpi') : __($t.'.actions.add_template') }}
            </x-pill-button>
            @endif
        @endcan
    </div>

    @error('kpi') <x-validation>{{ $message }}</x-validation> @enderror

    @if ($section === 'notifications')
        @can('manage-performance-evaluation')
            @php $customised = $this->notificationTemplates; @endphp
            <div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                <div class="border-b border-hairline-subtle bg-[#fafafa] px-5 py-3">
                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.notification_templates.title') }}</p>
                    <p class="mt-0.5 max-w-3xl text-[12px] leading-5 text-ink-muted">{{ __($t.'.notification_templates.hint') }}</p>
                </div>
                @foreach (\App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::EVENTS as $event)
                    @php $own = $customised->get($event); @endphp
                    <button type="button" wire:key="notification-{{ $event }}" wire:click="openNotificationTemplate('{{ $event }}')" class="flex w-full items-center gap-4 border-b border-hairline-subtle px-5 py-3 text-left transition last:border-b-0 hover:bg-[#fafafa]">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2 text-[13px] font-semibold text-ink">
                                {{ __($t.'.notification_templates.events.'.$event) }}
                                @if (in_array($event, \App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::MANDATORY, true))
                                    <span class="rounded-md bg-amber-50 px-1.5 py-px text-[10.5px] font-medium text-amber-700" title="{{ __($t.'.notification_templates.mandatory_hint') }}">{{ __($t.'.notification_templates.mandatory') }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-[12px] text-ink-muted">{{ $own?->subject ?? app(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::class)->defaultTemplate($event, $templateLocale)['subject'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-md px-2 py-0.5 text-[11px] font-medium {{ $own ? 'bg-violet-50 text-violet-700' : 'bg-[#f4f4f5] text-ink-faint' }}">{{ $own ? __($t.'.notification_templates.custom') : __($t.'.notification_templates.default') }}</span>
                    </button>
                @endforeach
            </div>
        @endcan
    @endif

    @if ($section === 'kpis')
        {{-- ───────────── KPI library ───────────── --}}
        <div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
            <div class="{{ $kpiColumns }} hidden border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5">
                <span class="hrm-eyebrow">{{ __($t.'.columns.kpi') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.direction') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.columns.measure') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.version') }}</span>
                <span class="hrm-eyebrow">{{ __($t.'.fields.status') }}</span>
                <span></span>
            </div>

            @forelse ($this->kpis as $kpi)
                @php [$badgeTone, $badgeDot] = $statusBadge[$kpi->status] ?? $statusBadge['draft']; @endphp
                <div wire:key="kpi-row-{{ $kpi->id }}" class="{{ $kpiColumns }} group flex flex-col gap-2.5 border-b border-hairline-subtle px-5 py-3.5 transition-colors last:border-b-0 hover:bg-[#fafafa] {{ $kpi->status === 'archived' ? 'opacity-60' : '' }}">
                    <div class="min-w-0">
                        <p class="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-ink">{{ $kpi->name }}</p>
                        <div class="mt-1 flex min-w-0 items-center gap-2 text-[12px] text-ink-faint">
                            <span class="hrm-num shrink-0 rounded-md bg-[#f4f4f5] px-1.5 py-px text-[11px] text-ink-muted">{{ $kpi->code }}</span>
                            <span class="truncate">{{ __($t.'.types.'.$kpi->type) }} · {{ __($t.'.perspectives.'.$kpi->perspective) }}</span>
                            @if ($kpi->data_source === 'calculated')
                                <span class="shrink-0 rounded-md bg-violet-50 px-1.5 py-px text-[11px] font-medium text-violet-700" title="{{ $kpi->formula }}">{{ __($t.'.formula.badge') }}</span>
                            @endif
                            @if ($kpi->source_metric)
                                <span class="shrink-0 rounded-md bg-sky-50 px-1.5 py-px text-[11px] font-medium text-sky-700" title="{{ __($t.'.metrics.'.$kpi->source_metric) }}">{{ __($t.'.metrics.auto_badge') }}</span>
                            @endif
                            @if ($kpi->integration_error)
                                <span class="shrink-0 rounded-md bg-rose-50 px-1.5 py-px text-[11px] font-medium text-rose-700" title="{{ $kpi->integration_error }}">{{ __($t.'.connector.stale') }}</span>
                            @endif
                            @if ($kpi->evidence_required)
                                <span class="shrink-0" title="{{ __($t.'.fields.evidence_required') }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 whitespace-nowrap text-[12.5px] text-ink-soft">
                        <span class="{{ $directionTone[$kpi->direction] ?? 'text-ink-faint' }}">
                            @if ($kpi->direction === 'higher_better')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>
                            @elseif ($kpi->direction === 'lower_better')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 7 6 6 4-4 8 8"/><path d="M21 11v6h-6"/></svg>
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7 3 12l5 5M16 7l5 5-5 5M3 12h18"/></svg>
                            @endif
                        </span>
                        {{ __($t.'.directions_short.'.$kpi->direction) }}
                    </div>

                    <div class="text-[12.5px] leading-tight">
                        <p class="text-ink-soft">{{ __($t.'.units.'.$kpi->unit) }}</p>
                        <p class="mt-0.5 text-ink-faint">{{ __($t.'.frequencies.'.$kpi->frequency) }}</p>
                    </div>

                    <div>
                        <span class="hrm-num rounded-md border border-hairline px-1.5 py-px text-[11px] text-ink-muted">v{{ $kpi->current_version }}</span>
                    </div>

                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $badgeTone }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                            {{ __($t.'.statuses.'.$kpi->status) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-0.5 md:justify-end md:opacity-0 md:transition-opacity md:group-hover:opacity-100 md:focus-within:opacity-100">
                        @can('manage-performance-evaluation')
                            <button type="button" wire:click="openKpiForm({{ $kpi->id }})" class="{{ $iconButton }}" title="{{ __($t.'.actions.edit') }}" aria-label="{{ __($t.'.actions.edit') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>
                            @if ($kpi->status !== 'archived')
                                <button type="button" wire:click="archiveKpi({{ $kpi->id }})" class="{{ $iconButton }} hover:!text-amber-700" title="{{ __($t.'.actions.archive') }}" aria-label="{{ __($t.'.actions.archive') }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8M10 12h4"/></svg>
                                </button>
                            @endif
                            <button type="button" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($t.'.actions.delete') }}" aria-label="{{ __($t.'.actions.delete') }}"
                                x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__($t.'.confirm_delete_kpi')), run: () => $wire.deleteKpi({{ $kpi->id }}) })">
                                <x-icons.delete-icon size="h-4 w-4" />
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-[13.5px] font-medium text-ink">{{ __($t.'.empty_kpis') }}</p>
                    <p class="mt-1 text-[12.5px] text-ink-faint">{{ __($t.'.empty_kpis_hint') }}</p>
                </div>
            @endforelse
        </div>
    @else
        {{-- ───────────── templates ───────────── --}}
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->templates as $template)
                @php
                    $weightSum = round((float) $template->items_sum_weight, 2);
                    $balanced = abs($weightSum - 100) < 0.001;
                    $kpiShare = (float) $template->kpi_weight_share;
                    [$badgeTone, $badgeDot] = $statusBadge[$template->status] ?? $statusBadge['draft'];
                    $positions = $template->positions->pluck('name');
                @endphp
                <div wire:key="kpi-template-{{ $template->id }}" class="group flex flex-col rounded-2xl border border-hairline bg-white shadow-card transition hover:border-zinc-300">
                    {{-- head --}}
                    <div class="flex items-start justify-between gap-3 px-4 pt-4">
                        <div class="min-w-0">
                            <p class="truncate text-[14.5px] font-semibold tracking-[-0.01em] text-ink">{{ $template->name }}</p>
                            <p class="mt-0.5 text-[12px] text-ink-faint">
                                {{ __($t.'.frequencies.'.$template->period_type) }}
                                @if ($template->code) · <span class="hrm-num">{{ $template->code }}</span> @endif
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $badgeTone }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $badgeDot }}"></span>
                            {{ __($t.'.statuses.'.$template->status) }}
                        </span>
                    </div>

                    {{-- KPI / competency split --}}
                    <div class="px-4 pt-3.5">
                        <div class="flex h-1.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                            <div class="h-full bg-ink" style="width: {{ $kpiShare }}%"></div>
                            <div class="h-full bg-zinc-300" style="width: {{ 100 - $kpiShare }}%"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-[11.5px]">
                            <span class="flex items-center gap-1.5 text-ink-muted"><span class="h-1.5 w-1.5 rounded-full bg-ink"></span>{{ __($t.'.split_kpi') }} <span class="hrm-num text-ink">{{ rtrim(rtrim(number_format($kpiShare, 2), '0'), '.') }}%</span></span>
                            <span class="flex items-center gap-1.5 text-ink-muted"><span class="h-1.5 w-1.5 rounded-full bg-zinc-300"></span>{{ __($t.'.split_competency') }} <span class="hrm-num text-ink">{{ rtrim(rtrim(number_format(100 - $kpiShare, 2), '0'), '.') }}%</span></span>
                        </div>
                    </div>

                    {{-- KPI items with weights --}}
                    <div class="mt-3.5 flex flex-col gap-2 border-t border-hairline-subtle px-4 pt-3">
                        @foreach ($template->items->take(4) as $item)
                            <div wire:key="kpi-template-{{ $template->id }}-item-{{ $item->id }}" class="flex items-center gap-3">
                                <span class="min-w-0 flex-1 truncate text-[12.5px] text-ink-soft">{{ $item->kpi?->name ?? '—' }}</span>
                                <div class="h-1 w-14 shrink-0 overflow-hidden rounded-full bg-[#f4f4f5]">
                                    <div class="h-full rounded-full bg-zinc-400" style="width: {{ min(100, (float) $item->weight) }}%"></div>
                                </div>
                                <span class="hrm-num w-10 shrink-0 text-right text-[11.5px] text-ink">{{ rtrim(rtrim(number_format((float) $item->weight, 2), '0'), '.') }}%</span>
                            </div>
                        @endforeach
                        @if ($template->items->count() > 4)
                            <p class="text-[11.5px] text-ink-faint">{{ __($t.'.more_items', ['count' => $template->items->count() - 4]) }}</p>
                        @endif
                        <div class="flex items-center gap-1.5 text-[11.5px] font-medium {{ $balanced ? 'text-emerald-700' : 'text-rose-600' }}">
                            @if ($balanced)
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                {{ __($t.'.weight_ok') }}
                            @else
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 8v5M12 16.5v.5"/><circle cx="12" cy="12" r="9"/></svg>
                                {{ __($t.'.weight_off', ['sum' => $weightSum]) }}
                            @endif
                        </div>
                    </div>

                    {{-- positions --}}
                    <div class="mt-3 flex flex-wrap gap-1.5 px-4">
                        @forelse ($positions->take(3) as $positionName)
                            <span class="max-w-full truncate rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11.5px] text-ink-muted">{{ $positionName }}</span>
                        @empty
                            <span class="rounded-md border border-dashed border-hairline px-2 py-0.5 text-[11.5px] text-ink-faint">{{ __($t.'.no_positions') }}</span>
                        @endforelse
                        @if ($positions->count() > 3)
                            <span class="hrm-num rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11.5px] text-ink-muted" title="{{ $positions->slice(3)->join(', ') }}">+{{ $positions->count() - 3 }}</span>
                        @endif
                    </div>

                    {{-- actions --}}
                    <div class="mt-auto flex items-center justify-end gap-0.5 px-3 pb-3 pt-3">
                        @can('manage-performance-evaluation')
                            <button type="button" wire:click="openTemplateForm({{ $template->id }})" class="{{ $iconButton }}" title="{{ __($t.'.actions.edit') }}" aria-label="{{ __($t.'.actions.edit') }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>
                            <button type="button" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($t.'.actions.delete') }}" aria-label="{{ __($t.'.actions.delete') }}"
                                x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__($t.'.confirm_delete_template')), run: () => $wire.deleteTemplate({{ $template->id }}) })">
                                <x-icons.delete-icon size="h-4 w-4" />
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-16 text-center md:col-span-2 xl:col-span-3">
                    <p class="text-[13.5px] font-medium text-ink">{{ __($t.'.empty_templates') }}</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ───────────── side forms ───────────── --}}
    @can('manage-performance-evaluation')
        <x-side-modal size="x-large">
            @if ($showSideMenu === 'kpi-form')
                <div class="flex h-full flex-col">
                    <h2 class="mb-6 text-[18px] font-semibold tracking-tight text-zinc-950">{{ $editingKpiId ? __($t.'.actions.edit_kpi') : __($t.'.actions.add_kpi') }}</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-label value="{{ __($t.'.fields.code') }}" />
                            <x-livewire-input mode="gray" name="kpiForm.code" wire:model="kpiForm.code" placeholder="SALES_PLAN" />
                            @error('kpiForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.fields.name') }}" />
                            <x-livewire-input mode="gray" name="kpiForm.name" wire:model="kpiForm.name" />
                            @error('kpiForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <x-label value="{{ __($t.'.fields.description') }}" />
                            <textarea wire:model="kpiForm.description" rows="2" class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-[13px] focus:border-zinc-400 focus:bg-white focus:outline-none"></textarea>
                        </div>

                        @foreach ([
                            'type' => ['types', \App\Models\PerformanceKpi::TYPES],
                            'direction' => ['directions', \App\Models\PerformanceKpi::DIRECTIONS],
                            'unit' => ['units', \App\Models\PerformanceKpi::UNITS],
                            'frequency' => ['frequencies', \App\Models\PerformanceKpi::FREQUENCIES],
                            'aggregation' => ['aggregations', \App\Models\PerformanceKpi::AGGREGATIONS],
                            'perspective' => ['perspectives', \App\Models\PerformanceKpi::PERSPECTIVES],
                            'status' => ['statuses', \App\Models\PerformanceKpi::STATUSES],
                        ] as $field => [$group, $options])
                            <div wire:key="kpi-field-{{ $field }}">
                                <x-label value="{{ __($t.'.fields.'.$field) }}" />
                                <div class="mt-1">
                                    <x-ui.filter-native-select wire:model="kpiForm.{{ $field }}">
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}">{{ __($t.'.'.$group.'.'.$option) }}</option>
                                        @endforeach
                                    </x-ui.filter-native-select>
                                </div>
                                @error('kpiForm.'.$field) <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        @endforeach

                        <div>
                            <x-label value="{{ __($t.'.fields.indicator_kind') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="kpiForm.indicator_kind">
                                    <option value="">—</option>
                                    <option value="lead">{{ __($t.'.indicator_kinds.lead') }}</option>
                                    <option value="lag">{{ __($t.'.indicator_kinds.lag') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <x-label value="{{ __($t.'.fields.source_metric') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model.live="kpiForm.source_metric">
                                    <option value="">{{ __($t.'.metrics.manual') }}</option>
                                    <option value="formula">{{ __($t.'.formula.option') }}</option>
                                    @foreach (array_keys(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\InternalKpiMetrics::METRICS) as $metric)
                                        <option value="{{ $metric }}">{{ __($t.'.metrics.'.$metric) }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </div>
                            <p class="mt-1.5 text-[12px] leading-5 text-ink-faint">{{ __($t.'.metrics.hint') }}</p>
                            @error('kpiForm.source_metric') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        @if (($kpiForm['source_metric'] ?? '') === 'formula')
                            <div class="flex flex-col gap-3 rounded-xl border border-hairline bg-[#fafafa] p-4 sm:col-span-2">
                                <div>
                                    <p class="text-[13px] font-semibold text-ink">{{ __($t.'.formula.title') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($t.'.formula.hint') }}</p>
                                </div>
                                <textarea wire:model="kpiForm.formula" rows="3" spellcheck="false" placeholder="ROUND({SALES_FACT} / {SALES_PLAN} * 100, 1)" class="hrm-num w-full rounded-xl border border-hairline bg-white px-3 py-2 font-mono text-[13px] text-ink focus:border-zinc-400 focus:outline-none"></textarea>
                                @error('kpiForm.formula') <x-validation>{{ $message }}</x-validation> @enderror
                                <p class="text-[11.5px] leading-5 text-ink-faint">{{ __($t.'.formula.syntax') }}</p>
                                <div>
                                    <button type="button" wire:click="testFormula" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[12.5px] font-semibold text-ink-soft hover:border-zinc-300 hover:text-ink">{{ __($t.'.formula.test') }}</button>
                                </div>
                            </div>
                        @endif

                        @if (($kpiForm['source_metric'] ?? '') === 'rest')
                            @php $c = $t.'.connector'; @endphp
                            <div class="flex flex-col gap-3 rounded-xl border border-hairline bg-[#fafafa] p-4 sm:col-span-2">
                                <div>
                                    <p class="text-[13px] font-semibold text-ink">{{ __($c.'.title') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($c.'.hint') }}</p>
                                </div>
                                <div>
                                    <x-label value="{{ __($c.'.fields.url') }}" />
                                    <x-livewire-input mode="gray" name="connectorForm.url" wire:model="connectorForm.url" placeholder="https://1c.example.az/odata/sales?tabel={tabel_no}&from={from}&to={to}" />
                                    @error('connectorForm.url') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <x-label value="{{ __($c.'.fields.auth') }}" />
                                        <div class="mt-1">
                                            <x-ui.filter-native-select wire:model.live="connectorForm.auth">
                                                @foreach (\App\Modules\PerformanceEvaluation\Application\Services\Kpi\RestKpiConnector::AUTH_TYPES as $auth)
                                                    <option value="{{ $auth }}">{{ __($c.'.auth.'.$auth) }}</option>
                                                @endforeach
                                            </x-ui.filter-native-select>
                                        </div>
                                    </div>
                                    @if (($connectorForm['auth'] ?? 'none') === 'basic')
                                        <div>
                                            <x-label value="{{ __($c.'.fields.username') }}" />
                                            <x-livewire-input mode="gray" name="connectorForm.username" wire:model="connectorForm.username" />
                                        </div>
                                    @endif
                                    @if (($connectorForm['auth'] ?? 'none') !== 'none')
                                        <div>
                                            <x-label value="{{ __($c.'.fields.'.(($connectorForm['auth'] ?? '') === 'basic' ? 'password' : 'token')) }}" />
                                            <x-livewire-input mode="gray" type="password" name="connectorForm.secret" wire:model="connectorForm.secret" placeholder="{{ $editingKpiId ? __($c.'.keep_secret') : '' }}" autocomplete="new-password" />
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <x-label value="{{ __($c.'.fields.value_path') }}" />
                                    <x-livewire-input mode="gray" name="connectorForm.value_path" wire:model="connectorForm.value_path" placeholder="data.0.total" />
                                    <p class="mt-1 text-[11.5px] leading-5 text-ink-faint">{{ __($c.'.value_path_hint') }}</p>
                                    @error('connectorForm.value_path') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <button type="button" wire:click="testConnector" wire:loading.attr="disabled" wire:target="testConnector" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[12.5px] font-semibold text-ink-soft hover:border-zinc-300 hover:text-ink">
                                        <svg class="h-4 w-4" wire:loading.class="animate-spin" wire:target="testConnector" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.6"/><path d="m21 4-9 9-3-3"/></svg>
                                        {{ __($c.'.test') }}
                                    </button>
                                </div>
                            </div>
                        @endif

                        <label class="flex items-center gap-2.5 text-[13px] text-zinc-700 sm:col-span-2">
                            <input type="checkbox" wire:model="kpiForm.evidence_required" class="h-4 w-4 rounded border-zinc-300">
                            {{ __($t.'.fields.evidence_required') }}
                        </label>
                    </div>

                    @if ($editingKpiId)
                        <p class="mt-4 rounded-xl bg-zinc-50 px-3 py-2 text-[12px] text-zinc-500">{{ __($t.'.version_hint') }}</p>
                    @endif

                    <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __($t.'.actions.cancel') }}</button>
                        <button type="button" wire:click="saveKpi" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __($t.'.actions.save') }}</button>
                    </div>
                </div>
            @endif

            @if ($showSideMenu === 'notification-template' && $notificationKey)
                <div class="flex h-full flex-col">
                    <p class="hrm-eyebrow">{{ __($t.'.sections.notifications') }} · {{ strtoupper($templateLocale) }}</p>
                    <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ __($t.'.notification_templates.events.'.$notificationKey) }}</h2>
                    <p class="mt-3 rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted">{{ __($t.'.notification_templates.placeholders_hint') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach (array_keys(\App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery::PLACEHOLDERS) as $placeholder)
                            <code class="rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11.5px] text-ink-soft">{{ '{'.$placeholder.'}' }}</code>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-col gap-4">
                        <div>
                            <x-label value="{{ __($t.'.notification_templates.subject') }}" />
                            <x-livewire-input mode="gray" name="notificationForm.subject" wire:model="notificationForm.subject" />
                            @error('notificationForm.subject') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.notification_templates.body') }}" />
                            <textarea wire:model="notificationForm.body" rows="6" class="w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none"></textarea>
                            @error('notificationForm.body') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>
                    <div class="mt-auto flex items-center justify-between gap-2.5 border-t border-hairline-subtle pt-5">
                        @if ($this->notificationTemplates->has($notificationKey))
                            <button type="button" wire:click="resetNotificationTemplate('{{ $notificationKey }}')" class="h-11 rounded-xl px-4 text-sm font-medium text-ink-muted hover:bg-[#fafafa] hover:text-ink">{{ __($t.'.notification_templates.reset') }}</button>
                        @else
                            <span></span>
                        @endif
                        <div class="flex items-center gap-2.5">
                            <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($t.'.actions.cancel') }}</button>
                            <button type="button" wire:click="saveNotificationTemplate" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($t.'.actions.save') }}</button>
                        </div>
                    </div>
                </div>
            @endif

            @if ($showSideMenu === 'template-form')
                <div class="flex h-full flex-col">
                    <h2 class="mb-6 text-[18px] font-semibold tracking-tight text-zinc-950">{{ $editingTemplateId ? __($t.'.actions.edit_template') : __($t.'.actions.add_template') }}</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-label value="{{ __($t.'.fields.name') }}" />
                            <x-livewire-input mode="gray" name="templateForm.name" wire:model="templateForm.name" />
                            @error('templateForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.fields.period_type') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="templateForm.period_type">
                                    @foreach (\App\Models\PerformanceKpiTemplate::PERIOD_TYPES as $option)
                                        <option value="{{ $option }}">{{ __($t.'.frequencies.'.$option) }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.fields.kpi_weight_share') }}" />
                            <x-livewire-input mode="gray" type="number" step="0.01" name="templateForm.kpi_weight_share" wire:model="templateForm.kpi_weight_share" />
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.fields.competency_weight_share') }}" />
                            <x-livewire-input mode="gray" type="number" step="0.01" name="templateForm.competency_weight_share" wire:model="templateForm.competency_weight_share" />
                        </div>
                        <div>
                            <x-label value="{{ __($t.'.fields.status') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="templateForm.status">
                                    <option value="active">{{ __($t.'.statuses.active') }}</option>
                                    <option value="archived">{{ __($t.'.statuses.archived') }}</option>
                                </x-ui.filter-native-select>
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <x-label value="{{ __($t.'.fields.competency_form') }}" />
                            <div class="mt-1">
                                <x-ui.filter-native-select wire:model="templateForm.performance_form_template_id">
                                    <option value="">{{ __($t.'.no_competency_form') }}</option>
                                    @foreach ($this->formTemplateOptions as $formTemplateId => $formTemplateName)
                                        <option value="{{ $formTemplateId }}">{{ $formTemplateName }}</option>
                                    @endforeach
                                </x-ui.filter-native-select>
                            </div>
                            <p class="mt-1 text-[11px] text-ink-faint">{{ __($t.'.competency_form_hint') }}</p>
                        </div>
                        @error('shares') <div class="sm:col-span-3"><x-validation>{{ $message }}</x-validation></div> @enderror

                        {{-- positions: searchable checklist; positions owned by another template show but cannot be picked --}}
                        <div class="sm:col-span-3"
                            x-data="{
                                query: '',
                                selected: $wire.entangle('templatePositionIds'),
                                options: @js($this->positionOptions),
                                takenLabel: @js(__($t.'.position_taken_by')),
                                has(id) { return (this.selected || []).map(Number).includes(id) },
                                toggle(option) {
                                    if (option.taken) { return }
                                    const current = (this.selected || []).map(Number);
                                    this.selected = this.has(option.id) ? current.filter((id) => id !== option.id) : [...current, option.id];
                                },
                                get filtered() {
                                    const needle = this.query.trim().toLocaleLowerCase('az');
                                    return needle === '' ? this.options : this.options.filter((option) => option.name.toLocaleLowerCase('az').includes(needle));
                                },
                                get chosen() { return this.options.filter((option) => this.has(option.id)) },
                            }">
                            <div class="flex items-center justify-between gap-3">
                                <x-label value="{{ __($t.'.fields.positions') }}" />
                                <div class="flex items-center gap-2">
                                    <span class="hrm-num rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-semibold text-ink-muted" x-text="`${chosen.length} {{ __($t.'.positions_selected') }}`"></span>
                                    <button type="button" x-show="chosen.length > 0" x-on:click="selected = []" class="text-[11.5px] font-medium text-ink-faint hover:text-ink">{{ __($t.'.actions.clear') }}</button>
                                </div>
                            </div>

                            <div class="mt-1.5 overflow-hidden rounded-xl border border-hairline bg-white">
                                {{-- chosen chips --}}
                                <div x-show="chosen.length > 0" class="flex flex-wrap gap-1.5 border-b border-hairline-subtle px-3 py-2.5">
                                    <template x-for="option in chosen" :key="'chip-' + option.id">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-ink py-1 pl-2.5 pr-1.5 text-[11.5px] font-medium text-white">
                                            <span x-text="option.name"></span>
                                            <button type="button" x-on:click="toggle(option)" class="flex h-4 w-4 items-center justify-center rounded-full text-white/70 hover:bg-white/15 hover:text-white" aria-label="{{ __($t.'.actions.remove') }}">
                                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                {{-- search --}}
                                <div class="flex items-center gap-2 border-b border-hairline-subtle px-3">
                                    <svg class="h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                    <input type="text" x-model="query" placeholder="{{ __($t.'.search_positions') }}"
                                        class="h-10 w-full border-0 bg-transparent px-0 text-[13px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0">
                                </div>

                                {{-- options --}}
                                <div class="hrm-scroll max-h-60 overflow-y-auto p-1.5">
                                    <template x-for="option in filtered" :key="'position-' + option.id">
                                        <button type="button" x-on:click="toggle(option)"
                                            :disabled="!! option.taken"
                                            class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition"
                                            :class="option.taken ? 'cursor-not-allowed opacity-50' : 'hover:bg-[#f4f4f5]'">
                                            <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[5px] border transition"
                                                :class="has(option.id) ? 'border-ink bg-ink text-white' : 'border-zinc-300 bg-white text-transparent'">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[13px]" :class="has(option.id) ? 'font-semibold text-ink' : 'text-ink-soft'" x-text="option.name"></span>
                                                <span x-show="option.taken" class="block truncate text-[11px] text-ink-faint" x-text="takenLabel.replace(':template', option.taken)"></span>
                                            </span>
                                        </button>
                                    </template>
                                    <p x-show="filtered.length === 0" class="px-3 py-6 text-center text-[12.5px] text-ink-faint">{{ __($t.'.no_results') }}</p>
                                </div>
                            </div>
                            <p class="mt-1.5 text-[11px] text-ink-faint">{{ __($t.'.positions_hint') }}</p>
                            @error('positions') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                    </div>

                    {{-- items --}}
                    <div class="mt-6">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[13px] font-semibold text-zinc-900">{{ __($t.'.fields.items') }}</p>
                            <span class="text-[12px] tabular-nums {{ abs(collect($templateItems)->sum(fn ($item) => (float) ($item['weight'] ?: 0)) - 100) > 0.001 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ __($t.'.template_weight', ['sum' => collect($templateItems)->sum(fn ($item) => (float) ($item['weight'] ?: 0))]) }}
                            </span>
                        </div>
                        @error('items') <x-validation>{{ $message }}</x-validation> @enderror

                        <div class="flex flex-col gap-2.5">
                            @foreach ($templateItems as $index => $item)
                                <div wire:key="template-item-{{ $index }}" class="rounded-xl border border-zinc-200/80 p-3">
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div class="col-span-2">
                                            <select wire:model="templateItems.{{ $index }}.performance_kpi_id" class="{{ $input }}">
                                                <option value="">{{ __($t.'.fields.choose_kpi') }}</option>
                                                @foreach ($this->kpis->where('status', '!=', 'archived') as $kpi)
                                                    <option value="{{ $kpi->id }}">{{ $kpi->code }} — {{ $kpi->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @foreach (['weight', 'target', 'range_min', 'range_max', 'threshold', 'stretch', 'cap'] as $field)
                                            <label class="block">
                                                <span class="text-[11px] text-zinc-400">{{ __($t.'.fields.'.$field) }}</span>
                                                <input type="number" step="any" wire:model="templateItems.{{ $index }}.{{ $field }}" class="{{ $input }}">
                                            </label>
                                        @endforeach
                                        <label class="flex items-end gap-2 pb-2 text-[12px] text-zinc-600">
                                            <input type="checkbox" wire:model="templateItems.{{ $index }}.target_editable" class="h-4 w-4 rounded border-zinc-300">
                                            {{ __($t.'.fields.target_editable') }}
                                        </label>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div>
                                            @error('items.'.$index) <x-validation>{{ $message }}</x-validation> @enderror
                                            @error('templateItems.'.$index.'.*') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                        <button type="button" wire:click="removeTemplateItem({{ $index }})" title="{{ __($t.'.actions.delete') }}" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50">
                                            <x-icons.delete-icon size="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addTemplateItem" class="mt-2.5 inline-flex h-9 items-center gap-1.5 rounded-xl border border-dashed border-zinc-300 px-3 text-[12px] font-medium text-zinc-600 hover:bg-zinc-50">
                            <x-icons.add-icon size="h-3.5 w-3.5" color="text-current" hover="text-current" />
                            {{ __($t.'.actions.add_item') }}
                        </button>
                        <p class="mt-2 text-[11px] leading-5 text-zinc-400">{{ __($t.'.band_hint') }}</p>
                    </div>

                    @if ($warnings !== [])
                        <div class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-[12px] text-amber-800">
                            @foreach ($warnings as $warning)
                                <p>{{ $warning }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __($t.'.actions.cancel') }}</button>
                        <button type="button" wire:click="saveTemplate" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __($t.'.actions.save') }}</button>
                    </div>
                </div>
            @endif
        </x-side-modal>
    @endcan
</div>
