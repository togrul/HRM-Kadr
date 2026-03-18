<div class="space-y-4">
    <x-surface-card :title="__('training_needs::dashboard.cards.full_lists')" icon="icons.folder-plus-icon">
        <div class="space-y-5">
            <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.labels.full_lists_hint') }}</p>

            <x-filter.nav class="min-w-0">
                <x-filter.item wire:click.prevent="switchEntity('needs')" :active="$entity === 'needs'">
                    {{ __('training_needs::dashboard.cards.need_queue') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('plans')" :active="$entity === 'plans'">
                    {{ __('training_needs::dashboard.cards.plan_items_board') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('sessions')" :active="$entity === 'sessions'">
                    {{ __('training_needs::dashboard.cards.training_calendar') }}
                </x-filter.item>
                <x-filter.item wire:click.prevent="switchEntity('deliveries')" :active="$entity === 'deliveries'">
                    {{ __('training_needs::dashboard.cards.delivered_trainings') }}
                </x-filter.item>
            </x-filter.nav>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.85fr)]">
                <div class="space-y-4">
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1.5fr)_minmax(220px,0.8fr)_160px]">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                            <x-label for="training-lists-search">{{ __('training_needs::dashboard.fields.search') }}</x-label>
                            <x-livewire-input mode="gray" id="training-lists-search" wire:model.live.debounce.300ms="search" />
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                            <x-label for="training-lists-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                            <select id="training-lists-status" wire:model.live="statusFilter" class="mt-2 h-11 w-full rounded-xl border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                @foreach ($this->statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.labels.visible_records') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-zinc-900">{{ $this->summary['visible'] }}</p>
                            <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.total_records_value', ['count' => $this->summary['total']]) }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-tight text-zinc-500">
                                @if ($entity === 'needs')
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.personnel') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.competency') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.priority') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.status') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.recommended_program') }}</th>
                                @elseif ($entity === 'plans')
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.plan') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.program') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.competency') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.participant_count') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.status') }}</th>
                                @elseif ($entity === 'sessions')
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.session_title') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.program') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.scheduled_start_at') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.participant_count') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.status') }}</th>
                                @else
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.personnel') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.program') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.completed_at') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.certificate_name') }}</th>
                                    <th class="px-4 py-3">{{ __('training_needs::dashboard.fields.status') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            @forelse ($this->rows as $row)
                                <tr class="align-top transition hover:bg-zinc-50/80 cursor-pointer {{ $selectedRowId === $row->id ? 'bg-sky-50/60' : '' }}" wire:click="selectRow({{ $row->id }})">
                                    @if ($entity === 'needs')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->personnel?->fullname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.priorities.'.$row->priority) }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.need_statuses.'.$row->status) }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->recommendedProgram?->title ?? '—' }}</td>
                                    @elseif ($entity === 'plans')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->plan?->title ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->program?->title ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->participant_count }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.plan_item_statuses.'.($row->review_status ?? 'draft')) }}</td>
                                    @elseif ($entity === 'sessions')
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->title }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->program?->title ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ optional($row->scheduled_start_at)->format('d.m.Y H:i') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->participants_count }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.session_statuses.'.$row->status) }}</td>
                                    @else
                                        <td class="px-4 py-3 text-zinc-800">{{ $row->personnel?->fullname ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->program?->title ?? '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ optional($row->completed_at)->format('d.m.Y H:i') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $row->certificate_name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-zinc-600">{{ __('training_needs::dashboard.delivery_result_statuses.'.$row->result_status) }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.full_lists') }}</td>
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

                <div class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-50 via-white to-emerald-50 p-5">
                    @if ($this->selectedRow)
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.labels.detail_panel') }}</p>
                                <p class="mt-2 text-xl font-semibold text-zinc-900">
                                    @if ($entity === 'needs')
                                        {{ $this->selectedRow->personnel?->fullname ?? '—' }}
                                    @elseif ($entity === 'plans')
                                        {{ $this->selectedRow->plan?->title ?? '—' }}
                                    @elseif ($entity === 'sessions')
                                        {{ $this->selectedRow->title ?? '—' }}
                                    @else
                                        {{ $this->selectedRow->personnel?->fullname ?? '—' }}
                                    @endif
                                </p>
                            </div>

                            <div class="grid gap-3">
                                @if ($entity === 'needs')
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.competency') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->competency?->name ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.recommended_program') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->recommendedProgram?->title ?? '—' }}</p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-sky-700">{{ __('training_needs::dashboard.fields.priority') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-sky-950">{{ __('training_needs::dashboard.priorities.'.$this->selectedRow->priority) }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-amber-700">{{ __('training_needs::dashboard.fields.status') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-amber-950">{{ __('training_needs::dashboard.need_statuses.'.$this->selectedRow->status) }}</p>
                                        </div>
                                    </div>
                                @elseif ($entity === 'plans')
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->program?->title ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.competency') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->competency?->name ?? '—' }}</p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-sky-700">{{ __('training_needs::dashboard.fields.participant_count') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-sky-950">{{ $this->selectedRow->participant_count }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-amber-700">{{ __('training_needs::dashboard.fields.status') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-amber-950">{{ __('training_needs::dashboard.plan_item_statuses.'.($this->selectedRow->review_status ?? 'draft')) }}</p>
                                        </div>
                                    </div>
                                @elseif ($entity === 'sessions')
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->program?->title ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.plan') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->plan?->title ?? '—' }}</p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-sky-700">{{ __('training_needs::dashboard.fields.participant_count') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-sky-950">{{ $this->selectedRow->participants_count }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-amber-700">{{ __('training_needs::dashboard.fields.status') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-amber-950">{{ __('training_needs::dashboard.session_statuses.'.$this->selectedRow->status) }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->program?->title ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.certificate_name') }}</p>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $this->selectedRow->certificate_name ?: '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-sky-700">{{ __('training_needs::dashboard.fields.completed_at') }}</p>
                                        <p class="mt-2 text-sm font-semibold text-sky-950">{{ optional($this->selectedRow->completed_at)->format('d.m.Y H:i') ?: '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-tight text-emerald-700">{{ __('training_needs::dashboard.fields.status') }}</p>
                                        <div class="mt-2">
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-tight text-emerald-800">
                                                {{ __('training_needs::dashboard.delivery_result_statuses.'.$this->selectedRow->result_status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if ($entity === 'needs')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->presentedReason() ?: '—' }}
                                </div>
                            @elseif ($entity === 'plans')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->review_note ?: '—' }}
                                </div>
                            @elseif ($entity === 'sessions')
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm leading-7 text-zinc-600">
                                    {{ $this->selectedRow->notes ?: '—' }}
                                </div>
                            @endif
                        </div>
                    @else
                        <x-ui.empty-state icon="icons.folder-plus-icon" :message="__('training_needs::dashboard.empty.select_list_row')" />
                    @endif
                </div>
            </div>
        </div>
    </x-surface-card>
</div>
