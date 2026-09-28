<div class="space-y-4">
    @unless($embedded)
        <x-surface-card :title="__('attendance::manual_entries.titles.page')" icon="icons.pending-icon">
            <p class="text-sm text-zinc-500">
                {{ __('attendance::manual_entries.descriptions.page') }}
            </p>
        </x-surface-card>
    @endunless

    @island(name: 'attendance-manual-workbench')
    @if($this->selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::manual_entries.labels.structure_scope') }}</x-small-badge>
            <span>{{ __('attendance::manual_entries.descriptions.scope') }}</span>
            <span class="font-medium">{{ $this->selectedStructureLabel }}</span>
        </div>
    @endif
    @endisland

    @island(name: 'attendance-manual-workbench')
    @if($canWrite)
        @include('attendance::livewire.attendance.partials.manual-entries.form')
    @endif
    @endisland

    @island(name: 'attendance-manual-queue')
    <x-surface-card :title="__('attendance::manual_entries.titles.queue')">
        <div class="mb-3 space-y-3">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::manual_entries.labels.approval_queue') }}</p>
                <p class="text-sm text-zinc-500">{{ __('attendance::manual_entries.descriptions.queue') }}</p>
            </div>

            <div class="w-full sm:w-48">
                <x-label for="manual-queue-status">{{ __('attendance::manual_entries.labels.status_filter') }}</x-label>
                <x-ui.select
                    id="manual-queue-status"
                    wire:model.live="queueStatus"
                >
                    <option value="pending">{{ __('attendance::manual_entries.statuses.pending') }}</option>
                    <option value="approved">{{ __('attendance::manual_entries.statuses.approved') }}</option>
                    <option value="rejected">{{ __('attendance::manual_entries.statuses.rejected') }}</option>
                    <option value="all">{{ __('attendance::manual_entries.statuses.all') }}</option>
                </x-ui.select>
            </div>
        </div>

        <div class="relative overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[
                        __('personnel::common.labels.number'),
                        __('attendance::manual_entries.labels.personnel'),
                        __('attendance::manual_entries.labels.date'),
                        __('attendance::manual_entries.labels.check_in'),
                        __('attendance::manual_entries.labels.check_out'),
                        __('attendance::manual_entries.labels.worked'),
                        __('attendance::manual_entries.labels.overtime'),
                        __('attendance::manual_entries.labels.late_minutes'),
                        __('attendance::manual_entries.labels.early_leave_minutes'),
                        __('attendance::manual_entries.labels.status'),
                        __('attendance::manual_entries.labels.entered_by'),
                        __('attendance::manual_entries.labels.approved_by'),
                        __('attendance::manual_entries.labels.actions')
                    ]">
                        @forelse($this->recentEntries as $entry)
                            @php
                                $statusClass = match($entry->approval_status) {
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                        <tr>
                            <x-table.td>{{ $entry->id }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-700">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900">
                                        {{ $entry->personnel?->fullname ?? $entry->tabel_no }}
                                    </span>
                                    <span class="text-xs font-mono uppercase text-zinc-500">{{ $entry->tabel_no }}</span>
                                    @if($entry->personnel?->structure_path)
                                        <span class="max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $entry->personnel->structure_path }}">
                                            {{ $entry->personnel->structure_name }}
                                        </span>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td>{{ optional($entry->date)->format('Y-m-d') }}</x-table.td>
                                <x-table.td>{{ $entry->check_in_at ?: '-' }}</x-table.td>
                                <x-table.td>{{ $entry->check_out_at ?: '-' }}</x-table.td>
                                <x-table.td>{{ $entry->worked_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->overtime_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->late_minutes }}</x-table.td>
                                <x-table.td>{{ $entry->early_leave_minutes }}</x-table.td>
                                <x-table.td>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs uppercase font-medium {{ $statusClass }}">
                                        {{ $this->approvalStatusLabel((string) $entry->approval_status) }}
                                    </span>
                                </x-table.td>
                                <x-table.td extraClasses="text-zinc-600">{{ $entry->enteredBy?->name ?? '-' }}</x-table.td>
                                <x-table.td extraClasses="text-zinc-600">{{ $entry->approvedBy?->name ?? '-' }}</x-table.td>
                                <x-table.td :isButton="true">
                                    @if($canApprove && $entry->approval_status === 'pending')
                                        {{-- rejecting needs a reason: the component validates it before the service runs --}}
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <div class="inline-flex items-center gap-2">
                                                <input
                                                    wire:model="rejectNotes.{{ $entry->id }}"
                                                    type="text"
                                                    required
                                                    maxlength="1000"
                                                    aria-label="{{ __('attendance::manual_entries.labels.reject_note') }}"
                                                    placeholder="{{ __('attendance::manual_entries.placeholders.reject_note') }}"
                                                    @class([
                                                        'h-8 w-44 rounded-[10px] border bg-[#f4f4f5] px-2.5 text-xs text-ink outline-none transition placeholder:text-ink-faint focus:bg-white focus:border-ink',
                                                        'border-rose-300' => $errors->has('rejectNotes.'.$entry->id),
                                                        'border-hairline' => ! $errors->has('rejectNotes.'.$entry->id),
                                                    ])
                                                />
                                                <x-button mode="approve" class="!h-8 !px-3 !text-xs uppercase" wire:click="approve({{ $entry->id }})">
                                                    {{ __('attendance::manual_entries.actions.approve') }}
                                                </x-button>
                                                <x-button mode="reject" class="!h-8 !px-3 !text-xs uppercase" wire:click="reject({{ $entry->id }})">
                                                    {{ __('attendance::manual_entries.actions.reject') }}
                                                </x-button>
                                            </div>
                                            @error('rejectNotes.'.$entry->id)
                                                <span class="text-[11px] text-rose-600">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-500">-</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="9" />
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>

        <div class="mt-3">
            {{ $this->recentEntries->links() }}
        </div>
    </x-surface-card>
    @endisland
</div>
