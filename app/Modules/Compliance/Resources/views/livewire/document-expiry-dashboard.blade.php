<div class="space-y-6 px-6 py-6">
    <section class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('compliance::documents.kicker') }}</x-ui.field-label>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('compliance::documents.title') }}</h1>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('compliance::documents.description') }}</p>
            </div>
            <button
                type="button"
                wire:click="exportCsv"
                class="inline-flex h-12 items-center justify-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300"
            >
                {{ __('compliance::documents.actions.export_csv') }}
            </button>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
            @foreach (['total', 'expired', 'expiring_30', 'expiring_60', 'missing', 'valid', 'compliance_score'] as $metric)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('compliance::documents.summary.'.$metric) }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">
                        {{ $summary[$metric] ?? 0 }}{{ $metric === 'compliance_score' ? '%' : '' }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    <x-ui.filter-panel>
            <x-ui.filter-field :label="__('compliance::documents.fields.search')">
                <x-ui.filter-input wire:model.live.debounce.300ms="search" placeholder="{{ __('compliance::documents.placeholders.search') }}" />
            </x-ui.filter-field>
            <x-ui.filter-field :label="__('compliance::documents.fields.document_type')">
                <x-ui.filter-native-select wire:model.live="type">
                    <option value="">{{ __('compliance::documents.filters.all_types') }}</option>
                    <option value="service_card">{{ __('compliance::documents.types.service_card') }}</option>
                    <option value="passport">{{ __('compliance::documents.types.passport') }}</option>
                    <option value="contract">{{ __('compliance::documents.types.contract') }}</option>
                </x-ui.filter-native-select>
            </x-ui.filter-field>
            <x-ui.filter-field :label="__('compliance::documents.fields.status')">
                <x-ui.filter-native-select wire:model.live="status">
                    <option value="">{{ __('compliance::documents.filters.all_statuses') }}</option>
                    @foreach (['missing', 'expired', 'expiring_30', 'expiring_60', 'valid'] as $option)
                        <option value="{{ $option }}">{{ __('compliance::documents.status.'.$option) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.filter-field>
            <div class="flex items-end">
                <x-ui.filter-reset-button wire:click="resetFilters" :label="__('compliance::documents.actions.reset_filters')">
                    {{ __('compliance::documents.actions.reset_short') }}
                </x-ui.filter-reset-button>
            </div>
    </x-ui.filter-panel>

    @if ($structureScores->isNotEmpty())
        <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('compliance::documents.sections.structure_scores') }}</x-ui.field-label>
            <div class="mt-4 grid gap-4 xl:grid-cols-5">
                @foreach ($structureScores as $score)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                        <div class="min-h-12 text-sm font-semibold leading-6 text-zinc-950">{{ $score['structure_name'] }}</div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="text-2xl font-semibold tracking-tight text-zinc-950">{{ $score['score'] }}%</span>
                            <span class="inline-flex rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-zinc-500">
                                {{ __('compliance::documents.labels.structure_score') }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">
                            {{ __('compliance::documents.labels.risk_summary', ['missing' => $score['missing'], 'expired' => $score['expired']]) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="relative min-h-[220px] -my-2 overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <x-table.tbl
                :headers="[
                    __('compliance::documents.columns.employee'),
                    __('compliance::documents.columns.document'),
                    __('compliance::documents.columns.expires_at'),
                    __('compliance::documents.columns.days_left'),
                    __('compliance::documents.columns.status'),
                ]"
                :title="__('compliance::documents.labels.result_count', ['count' => $rows->count()])"
            >
                @forelse ($rows as $row)
                    <tr>
                        <x-table.td>
                                <div class="font-semibold text-zinc-950">{{ $row['personnel_name'] }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $row['tabel_no'] }} · {{ $row['structure_name'] }} · {{ $row['position_name'] }}</div>
                        </x-table.td>
                        <x-table.td>
                                <div class="font-semibold text-zinc-800">{{ $row['document_label'] }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $row['document_number'] }}</div>
                        </x-table.td>
                        <x-table.td>
                            <span class="font-semibold text-zinc-800">{{ $row['expires_at'] }}</span>
                        </x-table.td>
                        <x-table.td>{{ $row['days_left'] ?? '—' }}</x-table.td>
                        <x-table.td>
                            <x-notification.chip mode="{{ in_array($row['status'], ['expired', 'missing'], true) ? 'rose' : (in_array($row['status'], ['expiring_30', 'expiring_60'], true) ? 'amber' : 'emerald') }}">
                                {{ __('compliance::documents.status.'.$row['status']) }}
                            </x-notification.chip>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty :rows="5" />
                @endforelse
            </x-table.tbl>
        </div>
    </div>
</div>
