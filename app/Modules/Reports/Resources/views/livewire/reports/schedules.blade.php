<div class="space-y-4 px-4 py-3 lg:px-5">
    @php
        $scheduleRows = $this->schedules();
        $candidates = $this->recipientCandidates();
        $selected = $this->selectedRecipients();
        $statusTones = ['sent' => 'text-emerald-700', 'failed' => 'text-rose-600', 'skipped' => 'text-zinc-500'];
    @endphp

    <x-surface-card :title="__('reports::schedules.title')" icon="icons.calendar-icon" class="rounded-2xl border-zinc-200/90 bg-white shadow-card" contentClass="p-5 lg:p-6">
        <p class="mb-4 text-sm text-zinc-500">{{ __('reports::schedules.description') }}</p>

        @if ($scheduleRows === [])
            <p class="text-sm text-zinc-500" data-testid="report-schedules-empty">{{ __('reports::schedules.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-[11px] uppercase text-zinc-400">
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.name') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.report') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.frequency') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.format') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.recipients') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.next_run') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('reports::schedules.columns.last_status') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('reports::schedules.columns.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scheduleRows as $row)
                            <tr wire:key="report-schedule-{{ $row['id'] }}" class="border-b border-zinc-100 align-top">
                                <td class="px-3 py-2 font-medium text-zinc-900">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-zinc-700">{{ $row['report'] }}</td>
                                <td class="px-3 py-2 text-zinc-700">{{ $row['frequency'] }}</td>
                                <td class="px-3 py-2 text-zinc-700">{{ $row['format'] }}</td>
                                <td class="px-3 py-2 text-zinc-700">{{ $row['recipients'] }}</td>
                                <td class="px-3 py-2 text-zinc-700">{{ $row['active'] ? $row['next_run'] : __('reports::schedules.labels.inactive') }}</td>
                                <td class="px-3 py-2">
                                    @if ($row['last_status'])
                                        <span class="{{ $statusTones[$row['last_status']] ?? 'text-zinc-500' }}" title="{{ $row['last_error'] }}">{{ __('reports::schedules.options.statuses.'.$row['last_status']) }}</span>
                                    @else
                                        <span class="text-zinc-400">{{ __('reports::schedules.labels.never') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <x-button mode="secondary" wire:click="sendNow({{ $row['id'] }})" wire:loading.attr="disabled" class="whitespace-nowrap">{{ __('reports::schedules.actions.send_now') }}</x-button>
                                        <x-button mode="secondary" wire:click="toggle({{ $row['id'] }})" class="whitespace-nowrap">{{ $row['active'] ? __('reports::schedules.actions.deactivate') : __('reports::schedules.actions.activate') }}</x-button>
                                        <x-button mode="secondary" wire:click="edit({{ $row['id'] }})" class="whitespace-nowrap">{{ __('reports::schedules.actions.edit') }}</x-button>
                                        <button type="button" wire:click="confirmScheduleDeletion({{ $row['id'] }})" class="rounded-md p-1 hover:bg-rose-50" aria-label="{{ __('reports::schedules.actions.delete') }}" title="{{ __('reports::schedules.actions.delete') }}">
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-surface-card>

    <x-surface-card :title="$editingId ? __('reports::schedules.edit_title') : __('reports::schedules.form_title')" icon="icons.report-chart-icon" class="rounded-2xl border-zinc-200/90 bg-white shadow-card" contentClass="p-5 lg:p-6">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.name') }}</span>
                    <x-ui.input wire:model="name" maxlength="120" />
                    @error('name') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.tab') }}</span>
                    <x-ui.select wire:model.live="tab">
                        @if ($reportOptions !== [])
                            <option value="standard">{{ __('reports::schedules.options.tabs.standard') }}</option>
                        @endif
                        @if ($sourceOptions !== [])
                            <option value="dynamic">{{ __('reports::schedules.options.tabs.dynamic') }}</option>
                        @endif
                    </x-ui.select>
                </label>

                @if ($tab === 'dynamic')
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.source') }}</span>
                        <x-ui.select wire:model.live="source">
                            @foreach ($sourceOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('source') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.group_by') }}</span>
                        <x-ui.select wire:model="groupBy">
                            @foreach ($groupOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.metric') }}</span>
                        <x-ui.select wire:model="metric">
                            @foreach ($metricOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </label>
                @else
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.report') }}</span>
                        <x-ui.select wire:model="report">
                            @foreach ($reportOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('report') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                @endif

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.structure') }}</span>
                    <x-ui.select wire:model="structureId">
                        <option value="">{{ __('reports::schedules.labels.all_structures') }}</option>
                        @foreach ($structureOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('structureId') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.period') }}</span>
                    <x-ui.select wire:model="period">
                        @foreach (['current_month', 'previous_month'] as $periodOption)
                            <option value="{{ $periodOption }}">{{ __('reports::schedules.options.periods.'.$periodOption) }}</option>
                        @endforeach
                    </x-ui.select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.frequency') }}</span>
                    <x-ui.select wire:model.live="frequency">
                        @foreach (['daily', 'weekly', 'monthly'] as $frequencyOption)
                            <option value="{{ $frequencyOption }}">{{ __('reports::schedules.options.frequencies.'.$frequencyOption) }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('frequency') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                @if ($frequency === 'weekly')
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.weekday') }}</span>
                        <x-ui.select wire:model="weekday">
                            @foreach (range(1, 7) as $weekdayOption)
                                <option value="{{ $weekdayOption }}">{{ __('reports::schedules.options.weekdays.'.$weekdayOption) }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('weekday') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                @elseif ($frequency === 'monthly')
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.month_day') }}</span>
                        <x-ui.select wire:model="monthDay">
                            @foreach (range(1, 28) as $dayOption)
                                <option value="{{ $dayOption }}">{{ $dayOption }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('monthDay') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                @endif

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.send_time') }}</span>
                    <x-ui.input type="time" wire:model="sendTime" step="900" />
                    @error('sendTime') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.format') }}</span>
                    <x-ui.select wire:model="format">
                        @foreach (['xlsx', 'csv', 'pdf'] as $formatOption)
                            <option value="{{ $formatOption }}">{{ __('reports::schedules.options.formats.'.$formatOption) }}</option>
                        @endforeach
                    </x-ui.select>
                    @if (! $pdfAvailable && $format === 'pdf')
                        <span class="mt-1 block text-xs text-amber-600">{{ __('reports::schedules.hints.pdf_unavailable') }}</span>
                    @endif
                    @error('format') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="space-y-2 border-t border-zinc-100 pt-4">
                <span class="block text-xs font-medium text-zinc-500">{{ __('reports::schedules.fields.recipients') }}</span>
                <p class="text-xs text-zinc-500">{{ __('reports::schedules.hints.recipients') }}</p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($selected as $recipient)
                        <span wire:key="report-schedule-recipient-{{ $recipient['id'] }}" class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-700">
                            {{ $recipient['name'] }} · {{ $recipient['email'] }}
                            <button type="button" wire:click="removeRecipient({{ $recipient['id'] }})" class="text-zinc-400 hover:text-rose-600" aria-label="{{ __('reports::schedules.actions.remove_recipient') }}">&times;</button>
                        </span>
                    @endforeach
                </div>

                <div class="max-w-md">
                    <x-ui.input icon="search" wire:model.live.debounce.300ms="recipientSearch" :placeholder="__('reports::schedules.fields.recipient_search')" />
                </div>

                @if ($candidates !== [])
                    <div class="max-w-md divide-y divide-zinc-100 rounded-xl border border-zinc-200">
                        @foreach ($candidates as $candidate)
                            <button type="button" wire:key="report-schedule-candidate-{{ $candidate['id'] }}" wire:click="addRecipient({{ $candidate['id'] }})" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                <span class="min-w-0 truncate text-zinc-800">{{ $candidate['name'] }} <span class="text-zinc-400">{{ $candidate['email'] }}</span></span>
                                <span class="shrink-0 text-xs font-medium text-zinc-500">{{ __('reports::schedules.actions.add_recipient') }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
                @error('recipientIds') <span class="block text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-col gap-3 border-t border-zinc-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-xs text-zinc-500">{{ __('reports::schedules.hints.owner_scope') }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($editingId)
                        <x-button mode="secondary" wire:click="cancelEdit" class="whitespace-nowrap">{{ __('reports::schedules.actions.cancel') }}</x-button>
                    @endif
                    <x-button type="submit" mode="black" wire:loading.attr="disabled" wire:target="save" class="whitespace-nowrap">
                        {{ $editingId ? __('reports::schedules.actions.save') : __('reports::schedules.actions.create') }}
                    </x-button>
                </div>
            </div>
        </form>
    </x-surface-card>

    <x-ui.delete-confirmation-modal />
</div>
