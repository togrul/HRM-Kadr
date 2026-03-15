<div class="space-y-4 px-4 py-3 lg:px-5">
    @php
        $chartMax = max(1, (float) collect($this->payload['chart'] ?? [])->max('value'));
    @endphp

    <x-surface-card :title="__('reports::dashboard.dynamic.title')" icon="icons.line-settings-icon" class="rounded-[2rem] border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]" bodyClass="rounded-[1.6rem] border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)]" contentClass="p-5 lg:p-6">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.source') }}</label>
                <select wire:model.live="source" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    @foreach ($sourceOptions as $option)
                        <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.group_by') }}</label>
                <select wire:model.live="groupBy" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    @foreach ($groupOptions as $option)
                        <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.metric') }}</label>
                <select wire:model.live="metric" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    @foreach ($metricOptions as $option)
                        <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.year') }}</label>
                <select wire:model.live="year" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    @foreach (range(now()->year - 4, now()->year + 1) as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.month') }}</label>
                <select wire:model.live="month" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    @foreach (range(1, 12) as $monthOption)
                        <option value="{{ $monthOption }}">{{ \Carbon\Carbon::create()->month($monthOption)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.structure') }}</label>
                <select wire:model.live="structureId" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 shadow-sm">
                    <option value="">{{ __('reports::dashboard.labels.all_structures') }}</option>
                    @foreach ($structureOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($canExport)
            <div class="mt-4 flex flex-col gap-3 border-t border-zinc-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-xs leading-6 text-zinc-500">{{ __('reports::dashboard.dynamic.resolved_source', ['source' => $this->payload['resolved_source']]) }}</p>
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                <x-button mode="secondary" wire:click="exportExcel" wire:loading.attr="disabled" class="whitespace-nowrap">{{ __('reports::dashboard.actions.export_excel') }}</x-button>
                <x-button mode="secondary" wire:click="exportCsv" wire:loading.attr="disabled" class="whitespace-nowrap">{{ __('reports::dashboard.actions.export_csv') }}</x-button>
                <a href="{{ $this->printUrl() }}" target="_blank" class="inline-flex h-11 items-center justify-center whitespace-nowrap rounded-2xl bg-zinc-900 px-5 text-sm font-medium text-white shadow-[0_8px_18px_rgba(24,24,27,0.12)]">
                    {{ __('reports::dashboard.actions.export_pdf') }}
                </a>
                </div>
            </div>
        @endif
    </x-surface-card>

    <x-surface-card :title="__('reports::dashboard.cards.dynamic_result')" icon="icons.report-chart-icon" class="rounded-[2rem] border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]" bodyClass="rounded-[1.6rem] border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)]" contentClass="p-5 lg:p-6">
        <div class="space-y-5">
            <p class="text-sm text-zinc-500">{{ __('reports::dashboard.dynamic.resolved_source', ['source' => $this->payload['resolved_source']]) }}</p>

            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($this->payload['summary'] as $item)
                    <div class="rounded-[1.4rem] border border-zinc-200/90 bg-zinc-50/70 px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ $item['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 xl:grid-cols-[0.9fr,1.1fr]">
                <x-surface-card :title="__('reports::dashboard.cards.visualization')" icon="icons.training-icon" class="rounded-[1.75rem] border-zinc-200/90 bg-white shadow-[0_8px_24px_rgba(15,23,42,0.04)]" bodyClass="rounded-[1.35rem] border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)]" contentClass="p-4">
                    <div class="space-y-3">
                        @forelse ($this->payload['chart'] as $bar)
                            @php
                                $rawValue = is_numeric($bar['value']) ? (float) $bar['value'] : 0.0;
                                $width = $rawValue > 0
                                    ? max(4, min(100, round(($rawValue / $chartMax) * 100, 1)))
                                    : 2.5;
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-zinc-800">{{ $bar['label'] }}</span>
                                    <span class="text-zinc-500">{{ $bar['value'] }}</span>
                                </div>
                                <div class="h-[18px] rounded-md border border-zinc-100 bg-zinc-50 px-[6px] py-[5px]">
                                    <div
                                        class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_8px,transparent_8px_12px)]"
                                        style="width: {{ $width }}%"
                                    ></div>
                                </div>
                            </div>
                        @empty
                            @include('reports::components.report-placeholder', [
                                'title' => __('reports::dashboard.cards.visualization'),
                                'message' => __('reports::dashboard.empty.no_chart_data'),
                                'compact' => true,
                            ])
                        @endforelse
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('reports::dashboard.cards.table_view')" icon="icons.document-icon" class="rounded-[1.75rem] border-zinc-200/90 bg-white shadow-[0_8px_24px_rgba(15,23,42,0.04)]" bodyClass="rounded-[1.35rem] border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)]" contentClass="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
                                    @foreach ($this->payload['columns'] as $column)
                                        <th class="px-3 py-2">{{ $column['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse ($this->payload['rows'] as $row)
                                    <tr>
                                        @foreach ($this->payload['columns'] as $column)
                                            <td class="px-3 py-3 text-zinc-700">{{ data_get($row, $column['key']) }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($this->payload['columns']) }}" class="px-3 py-4">
                                            @include('reports::components.report-placeholder', [
                                                'title' => __('reports::dashboard.cards.table_view'),
                                                'message' => __('reports::dashboard.empty.no_report_data'),
                                                'compact' => true,
                                            ])
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-surface-card>
            </div>
        </div>
    </x-surface-card>
</div>
