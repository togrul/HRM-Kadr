<div class="flex flex-col gap-5 px-4 py-5 text-zinc-950 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('audit::activity.header.kicker') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">{{ __('audit::activity.header.title') }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500">{{ __('audit::activity.header.subtitle') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a
                    href="{{ $this->exportUrl('xlsx') }}"
                    class="inline-flex h-9 items-center justify-center rounded-xl border border-zinc-950 bg-zinc-950 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300"
                >
                    {{ __('audit::activity.actions.export_xlsx') }}
                </a>
                <a
                    href="{{ $this->exportUrl('csv') }}"
                    class="inline-flex h-9 items-center justify-center rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-300"
                >
                    {{ __('audit::activity.actions.export_csv') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[520px]">
            @foreach ([
                ['label' => __('audit::activity.metrics.total'), 'value' => $summary['total']],
                ['label' => __('audit::activity.metrics.today'), 'value' => $summary['today']],
                ['label' => __('audit::activity.metrics.profile_opened'), 'value' => $summary['profile_opened']],
                ['label' => __('audit::activity.metrics.users'), 'value' => $summary['users']],
            ] as $metric)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ $metric['label'] }}</p>
                    <p class="mt-1 text-xl font-semibold tracking-tight text-zinc-950">{{ number_format($metric['value']) }}</p>
                </div>
            @endforeach
        </div>
    </header>

    <x-ui.filter-panel>
            <x-ui.filter-field :label="__('audit::activity.filters.search')">
                <x-ui.filter-input
                    wire:model.live.debounce.350ms="search"
                    placeholder="{{ __('audit::activity.filters.search_placeholder') }}"
                />
            </x-ui.filter-field>

            <x-ui.filter-field :label="__('audit::activity.filters.log_name')">
                <x-ui.filter-native-select wire:model.live="logName">
                    <option value="">{{ __('audit::activity.filters.all') }}</option>
                    @foreach ($logNameOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.filter-field>

            <x-ui.filter-field :label="__('audit::activity.filters.event')">
                <x-ui.filter-native-select wire:model.live="event">
                    <option value="">{{ __('audit::activity.filters.all') }}</option>
                    @foreach ($eventOptions as $option)
                        <option value="{{ $option }}">{{ $this->eventLabel($option) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.filter-field>

            <x-ui.filter-field :label="__('audit::activity.filters.from')">
                <x-ui.filter-input type="date" icon="" wire:model.live="dateFrom" />
            </x-ui.filter-field>

            <x-ui.filter-field :label="__('audit::activity.filters.to')">
                <x-ui.filter-input type="date" icon="" wire:model.live="dateTo" />
            </x-ui.filter-field>

            <x-ui.filter-field :label="__('audit::activity.filters.per_page')">
                <x-ui.filter-native-select wire:model.live="perPage" class="min-w-[88px]">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </x-ui.filter-native-select>
            </x-ui.filter-field>

            <div class="flex items-end">
                <x-ui.filter-reset-button
                    wire:click="resetFilters"
                    :label="__('audit::activity.actions.reset_filters')"
                >
                    {{ __('audit::activity.actions.reset_short') }}
                </x-ui.filter-reset-button>
            </div>
    </x-ui.filter-panel>

    <main>
        <section class="min-w-0">
            <x-table.tbl
                :title="__('audit::activity.list.total', ['count' => $activities->total()])"
                :headers="[
                    __('audit::activity.table.time'),
                    __('audit::activity.table.event'),
                    __('audit::activity.table.description'),
                    __('audit::activity.table.actor'),
                    __('audit::activity.table.subject'),
                    __('audit::activity.table.action'),
                ]"
            >
                @forelse ($activities as $activity)
                    @php($tone = $this->eventTone($activity->event))
                    <tr wire:key="audit-row-{{ $activity->id }}" class="transition-colors hover:bg-zinc-50/80">
                        <x-table.td>
                            <span class="block text-sm font-semibold text-zinc-800">{{ $activity->created_at?->format('d.m.Y') }}</span>
                            <span class="block text-xs text-zinc-400">{{ $activity->created_at?->format('H:i:s') }}</span>
                        </x-table.td>

                        <x-table.td>
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $tone === 'emerald',
                                'bg-sky-50 text-sky-700' => $tone === 'sky',
                                'bg-amber-50 text-amber-700' => $tone === 'amber',
                                'bg-rose-50 text-rose-700' => $tone === 'rose',
                                'bg-zinc-100 text-zinc-700' => $tone === 'zinc',
                            ])>
                                {{ $this->eventLabel($activity->event) }}
                            </span>
                            <span class="mt-1 block text-xs font-medium text-zinc-400">{{ $activity->log_name ?: __('audit::activity.labels.no_log_name') }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[260px] max-w-[380px]">
                            <span class="line-clamp-2 text-sm font-semibold text-zinc-900">{{ $this->descriptionLabel($activity->description) }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[180px] max-w-[260px]">
                            <span class="line-clamp-2 text-sm font-medium text-zinc-700">{{ $this->actorLabel($activity) }}</span>
                        </x-table.td>

                        <x-table.td :standart-width="true" extra-classes="min-w-[180px] max-w-[260px]">
                            <span class="line-clamp-2 text-sm text-zinc-600">{{ $this->subjectLabel($activity) }}</span>
                        </x-table.td>

                        <x-table.td :is-button="true">
                            <button
                                type="button"
                                wire:key="audit-open-{{ $activity->id }}"
                                wire:click.stop="selectActivity({{ $activity->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-zinc-950 bg-zinc-950 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 disabled:cursor-wait disabled:opacity-60"
                                aria-label="{{ __('audit::activity.actions.open_detail') }}"
                                title="{{ __('audit::activity.actions.open_detail') }}"
                            >
                                {{ __('audit::activity.table.action') }}
                            </button>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <p class="text-sm font-semibold text-zinc-700">{{ __('audit::activity.empty.title') }}</p>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('audit::activity.empty.subtitle') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>

            <div class="mt-3">
                {{ $activities->links() }}
            </div>
        </section>
    </main>

    @if ($selectedActivity)
        <x-ui.side-panel
            title-id="audit-detail-title"
            close-action="$wire.closeDetail()"
            :close-label="__('audit::activity.actions.close_detail')"
            width="3xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-6 py-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('audit::activity.detail.kicker') }}</p>
                    <h2 id="audit-detail-title" class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">#{{ $selectedActivity->id }}</h2>
                    <p class="mt-2 text-xs leading-5 text-zinc-500">{{ $selectedActivity->created_at?->format('d.m.Y H:i:s') }}</p>
                </div>

                <button
                    x-ref="closeButton"
                    type="button"
                    x-on:click="close()"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-50 text-xl font-semibold text-zinc-500 ring-1 ring-zinc-200 transition hover:bg-zinc-100 hover:text-zinc-950"
                    aria-label="{{ __('audit::activity.actions.close_detail') }}"
                    title="{{ __('audit::activity.actions.close_detail') }}"
                >
                    &times;
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <div class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.description') }}</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-zinc-950">{{ $this->descriptionLabel($selectedActivity->description) }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.log_name') }}</p>
                        <p class="mt-1 break-words text-sm font-semibold text-zinc-900">{{ $selectedActivity->log_name ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.event') }}</p>
                        <p class="mt-1 break-words text-sm font-semibold text-zinc-900">{{ $this->eventLabel($selectedActivity->event) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.actor') }}</p>
                        <p class="mt-1 break-words text-sm font-semibold text-zinc-900">{{ $this->actorLabel($selectedActivity) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.subject') }}</p>
                        <p class="mt-1 break-words text-sm font-semibold text-zinc-900">{{ $this->subjectLabel($selectedActivity) }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('audit::activity.detail.properties') }}</p>
                    <div class="mt-3 space-y-2">
                        @forelse ($this->propertyRows($selectedActivity) as $row)
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                                <p class="text-xs font-semibold text-zinc-500">{{ $row['key'] }}</p>
                                <pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-zinc-800">{{ $row['value'] }}</pre>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-400">
                                {{ __('audit::activity.detail.no_properties') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-100 bg-white px-6 py-4">
                <div class="flex justify-end">
                    <button type="button" x-on:click="close()" class="h-11 rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">
                        {{ __('audit::activity.actions.close_short') }}
                    </button>
                </div>
            </div>
        </x-ui.side-panel>
    @endif
</div>
