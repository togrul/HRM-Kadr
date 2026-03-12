<div class="space-y-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.cards.full_lists')" icon="icons.performance-icon">
        <div class="space-y-5">
            <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.labels.full_lists_hint') }}</p>

            <x-filter.nav class="min-w-0">
                <x-filter.item wire:click.prevent="switchEntity('forms')" :active="$entity === 'forms'">
                    {{ __('performance_evaluation::dashboard.cards.recent_forms') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('templates')" :active="$entity === 'templates'">
                    {{ __('performance_evaluation::dashboard.cards.recent_templates') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('items')" :active="$entity === 'items'">
                    {{ __('performance_evaluation::dashboard.cards.recent_template_items') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('attempts')" :active="$entity === 'attempts'">
                    {{ __('performance_evaluation::dashboard.cards.recent_test_attempts') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('weak_links')" :active="$entity === 'weak_links'">
                    {{ __('performance_evaluation::dashboard.cards.weak_links') }}
                </x-filter.item>
            </x-filter.nav>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)]">
                <div class="space-y-4">
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1.5fr)_minmax(220px,0.8fr)_160px]">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                            <x-label for="performance-lists-search">{{ __('performance_evaluation::dashboard.fields.search') }}</x-label>
                            <x-livewire-input mode="gray" id="performance-lists-search" wire:model.live.debounce.300ms="search" />
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                            <x-label for="performance-lists-filter">{{ __('performance_evaluation::dashboard.fields.status_filter') }}</x-label>
                            <select id="performance-lists-filter" wire:model.live="filter" class="mt-2 h-11 w-full rounded-xl border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                @foreach ($this->filterOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('performance_evaluation::dashboard.labels.visible_records') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-zinc-900">{{ $this->summary['visible'] }}</p>
                            <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.labels.total_records_value', ['count' => $this->summary['total']]) }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                @if ($entity === 'forms')
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.personnel') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.cycle') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.template') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.final_category') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.score') }}</th>
                                @elseif ($entity === 'templates')
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.template_name') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.template_code') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.status') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.labels.sections_count', ['count' => '']) }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.description') }}</th>
                                @elseif ($entity === 'items')
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.item') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.section') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.template') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.competency') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.low_score_threshold') }}</th>
                                @elseif ($entity === 'attempts')
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.personnel') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.test_bank') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.status') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.score') }}</th>
                                @else
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.competency') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.personnel') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.priority') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.status') }}</th>
                                    <th class="px-4 py-3">{{ __('performance_evaluation::dashboard.fields.links_count') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            @forelse ($this->rows as $row)
                                <tr class="align-top transition hover:bg-zinc-50/80 cursor-pointer {{ $selectedRowId === $row->id ? 'bg-sky-50/60' : '' }}" wire:click="selectRow({{ $row->id }})">
                                    @if ($entity === 'forms')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->personnel?->fullname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->cycle?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->template?->name ?: $row->template?->code ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->final_category ? __('performance_evaluation::dashboard.categories.'.$row->final_category) : '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->final_score ?? '—' }}</td>
                                    @elseif ($entity === 'templates')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->name }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->code ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->is_active ? __('performance_evaluation::dashboard.labels.active') : __('performance_evaluation::dashboard.labels.inactive') }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->sections_count }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ \Illuminate\Support\Str::limit((string) $row->description, 80) ?: '—' }}</td>
                                    @elseif ($entity === 'items')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->name }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->section?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->section?->template?->name ?: $row->section?->template?->code ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->low_score_threshold }}</td>
                                    @elseif ($entity === 'attempts')
                                        <td class="px-4 py-3 text-zinc-800">#{{ $row->id }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->session?->personnel?->fullname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->session?->bank?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('performance_evaluation::dashboard.test_statuses.'.$row->status) }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->score ?? '—' }}</td>
                                    @else
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->form?->personnel?->fullname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.priorities.'.($row->trainingNeed?->priority ?? 'medium')) }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.need_statuses.'.($row->trainingNeed?->status ?? 'draft')) }}</td>
                                        <td class="px-4 py-3 text-zinc-600">1</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.empty.full_lists') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                            </table>
                        </div>
                        <div class="border-t border-zinc-200 px-4 py-3">
                            {{ $this->rows->links() }}
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-50 via-white to-sky-50 p-5">
                    @if ($this->selectedRow)
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('performance_evaluation::dashboard.labels.detail_panel') }}</p>
                                <p class="mt-2 text-xl font-semibold text-zinc-900">
                                    @if ($entity === 'forms')
                                        {{ $this->selectedRow->personnel?->fullname ?? '—' }}
                                    @elseif ($entity === 'templates')
                                        {{ $this->selectedRow->name ?: '—' }}
                                    @elseif ($entity === 'items')
                                        {{ $this->selectedRow->name ?: '—' }}
                                    @elseif ($entity === 'attempts')
                                        #{{ $this->selectedRow->id }}
                                    @else
                                        {{ $this->selectedRow->competency?->name ?? '—' }}
                                    @endif
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @if ($entity === 'forms')
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.cycle') }}: {{ $this->selectedRow->cycle?->name ?? '—' }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.template') }}: {{ $this->selectedRow->template?->name ?: $this->selectedRow->template?->code ?: '—' }}</x-small-badge>
                                    <x-small-badge :mode="$this->selectedRow->final_category === 'weak' ? 'red' : ($this->selectedRow->final_category === 'high' ? 'green' : 'amber')">
                                        {{ __('performance_evaluation::dashboard.fields.final_category') }}: {{ $this->selectedRow->final_category ? __('performance_evaluation::dashboard.categories.'.$this->selectedRow->final_category) : '—' }}
                                    </x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.score') }}: {{ $this->selectedRow->final_score ?? '—' }}</x-small-badge>
                                @elseif ($entity === 'templates')
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.template_code') }}: {{ $this->selectedRow->code ?: '—' }}</x-small-badge>
                                    <x-small-badge :mode="$this->selectedRow->is_active ? 'green' : 'secondary'">{{ $this->selectedRow->is_active ? __('performance_evaluation::dashboard.labels.active') : __('performance_evaluation::dashboard.labels.inactive') }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.sections_count', ['count' => $this->selectedRow->sections_count]) }}</x-small-badge>
                                @elseif ($entity === 'items')
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.section') }}: {{ $this->selectedRow->section?->name ?? '—' }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.template') }}: {{ $this->selectedRow->section?->template?->name ?: $this->selectedRow->section?->template?->code ?: '—' }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.weight_percent') }}: {{ number_format((float) $this->selectedRow->weight_percent, 2) }}%</x-small-badge>
                                    <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.fields.low_score_threshold') }}: {{ number_format((float) $this->selectedRow->low_score_threshold, 2) }}</x-small-badge>
                                @elseif ($entity === 'attempts')
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.personnel') }}: {{ $this->selectedRow->session?->personnel?->fullname ?? '—' }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.test_bank') }}: {{ $this->selectedRow->session?->bank?->name ?? '—' }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.status') }}: {{ __('performance_evaluation::dashboard.test_statuses.'.$this->selectedRow->status) }}</x-small-badge>
                                    <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.fields.score') }}: {{ $this->selectedRow->score ?? '—' }}</x-small-badge>
                                @else
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.personnel') }}: {{ $this->selectedRow->form?->personnel?->fullname ?? '—' }}</x-small-badge>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.priority') }}: {{ __('training_needs::dashboard.priorities.'.($this->selectedRow->trainingNeed?->priority ?? 'medium')) }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.status') }}: {{ __('training_needs::dashboard.need_statuses.'.($this->selectedRow->trainingNeed?->status ?? 'draft')) }}</x-small-badge>
                                @endif
                            </div>

                            @if ($entity === 'templates')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->description ?: '—' }}
                                </div>
                            @elseif ($entity === 'weak_links')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->trainingNeed?->presentedReason() ?? '—' }}
                                </div>
                            @elseif ($entity === 'items')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->competency?->name ?? __('performance_evaluation::dashboard.labels.no_competency') }}
                                </div>
                            @endif
                        </div>
                    @else
                        <x-ui.empty-state icon="icons.performance-icon" :message="__('performance_evaluation::dashboard.empty.select_list_row')" />
                    @endif
                </div>
            </div>
        </div>
    </x-surface-card>
</div>
