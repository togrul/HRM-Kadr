<div class="flex flex-col space-y-6">
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-zinc-500" id="slide-over-title">
            {!! $title ?? '' !!}
        </h2>
        <p class="mt-1 text-[12px] text-ink-faint">{{ __('personnel::vacations.hints.work_year') }}</p>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
            <p class="text-[11px] font-medium text-zinc-400">{{ __('personnel::common.labels.total_days') }}</p>
            <p class="hrm-num mt-0.5 text-xl font-semibold text-zinc-900">{{ $this->totals['total'] }}</p>
        </div>
        <div class="rounded-xl bg-zinc-50 px-3 py-2.5 ring-1 ring-inset ring-zinc-200/70">
            <p class="text-[11px] font-medium text-zinc-400">{{ __('personnel::common.labels.used') }}</p>
            <p class="hrm-num mt-0.5 text-xl font-semibold text-zinc-700">{{ $this->totals['used'] }}</p>
        </div>
        <div class="rounded-xl bg-emerald-50 px-3 py-2.5 ring-1 ring-inset ring-emerald-100">
            <p class="text-[11px] font-medium text-zinc-400">{{ __('personnel::common.labels.remaining_days') }}</p>
            <p class="hrm-num mt-0.5 text-xl font-semibold text-emerald-700">{{ $this->totals['remaining'] }}</p>
        </div>
    </div>

    @if ($reservedSequence !== null)
        <div class="flex items-center space-x-2">
            <div class="flex w-1/3 flex-col">
                <x-ui.select-dropdown
                    :label="__('personnel::common.labels.reserved_month')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="reservedMonthId"
                    :model="$this->monthOptions"
                />
            </div>
            <x-button mode="black" class="mt-5" wire:click="setMonth">{{ __('personnel::common.actions.save') }}</x-button>
            <x-button mode="danger" class="mt-5" wire:click="resetVacation">{{ __('personnel::common.actions.cancel') }}</x-button>
        </div>
    @endif

    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <x-table.tbl :headers="[__('personnel::vacations.labels.work_year'), __('personnel::vacations.labels.entitlement'), __('personnel::common.labels.used'), __('personnel::common.labels.remaining_days'), __('personnel::common.labels.reserved_month'), __('services::common.labels.action')]">
                @forelse ($this->workYears as $year)
                    @php
                        $current = $year['start'] <= now()->toDateString() && now()->toDateString() <= $year['end'];
                        $b = $year['breakdown'];
                    @endphp
                    <tr wire:key="work-year-{{ $year['sequence'] }}" @class(['border-l-4 border-ink' => $current])>
                        <x-table.td>
                            <div class="flex flex-col leading-tight">
                                <span class="hrm-num text-[13px] font-medium text-zinc-800">{{ $year['label'] }}</span>
                                @unless ($year['available'])
                                    <span class="text-[11px] text-amber-600">{{ __('personnel::vacations.labels.available_from', ['date' => \Illuminate\Support\Carbon::parse($year['available_from'])->format('d.m.Y')]) }}</span>
                                @endunless
                            </div>
                        </x-table.td>
                        <x-table.td style="white-space: normal !important;">
                            <div class="flex flex-col gap-1">
                                <span class="hrm-num text-sm font-semibold text-zinc-900">{{ $year['total'] }} <span class="text-[11px] font-normal text-ink-faint">{{ __('personnel::vacations.labels.days') }}</span></span>
                                <div class="flex flex-wrap gap-1 text-[11px] text-zinc-500">
                                    @if (in_array($year['strategy'], ['legacy', 'opening'], true))
                                        <x-small-badge mode="secondary">{{ __('personnel::vacations.strategies.'.$year['strategy']) }}</x-small-badge>
                                    @elseif ($year['strategy'] === 'ranked')
                                        <x-small-badge mode="secondary">{{ __('personnel::vacations.strategies.ranked') }}</x-small-badge>
                                    @else
                                        <span>{{ __('personnel::vacations.breakdown.base', ['days' => $b['base'] ?? 0]) }}</span>
                                        @if (($b['seniority'] ?? 0) > 0)
                                            <span>· {{ __('personnel::vacations.breakdown.seniority', ['days' => $b['seniority'], 'years' => $b['seniority_years'] ?? 0]) }}</span>
                                        @endif
                                        @if (($b['children'] ?? 0) > 0)
                                            <span>· {{ __('personnel::vacations.breakdown.children', ['days' => $b['children']]) }}</span>
                                        @endif
                                        @if (($b['conditions'] ?? 0) > 0)
                                            <span>· {{ __('personnel::vacations.breakdown.conditions', ['days' => $b['conditions']]) }}</span>
                                        @endif
                                    @endif
                                    @if ($year['opening'] > 0)
                                        <span>· {{ __('personnel::vacations.breakdown.opening', ['days' => $year['opening']]) }}</span>
                                    @endif
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <span class="hrm-num text-sm font-medium text-rose-500">{{ $year['used'] + $year['compensated'] }}</span>
                            @if ($year['compensated'] > 0)
                                <span class="block text-[11px] text-ink-faint">{{ __('personnel::vacations.labels.compensated', ['days' => $year['compensated']]) }}</span>
                            @endif
                        </x-table.td>
                        <x-table.td>
                            <span class="hrm-num text-sm font-semibold text-emerald-600">{{ $year['remaining'] }}</span>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-zinc-900">{{ $year['reserved_month'] ? array_search($year['reserved_month'], $months) : '—' }}</span>
                                <button type="button" wire:click="updateMonth({{ $year['sequence'] }})" title="{{ __('personnel::common.actions.edit') }}">
                                    <x-icons.edit-icon></x-icons.edit-icon>
                                </button>
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <button type="button" wire:click="goToVacations('{{ $year['start'] }}', '{{ $year['end'] }}')"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-700"
                                title="{{ __('personnel::vacations.labels.open_vacations') }}">
                                <x-icons.document-icon></x-icons.document-icon>
                            </button>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="flex items-center justify-center py-4">
                                <span class="font-medium">{{ __('personnel::vacations.messages.empty') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>
    </div>

    @if ($this->canManageOpening)
        <section class="space-y-3 rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('personnel::vacations.titles.opening') }}</h3>
                <p class="mt-0.5 text-[12px] text-ink-faint">{{ __('personnel::vacations.hints.opening') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="flex flex-col sm:col-span-2">
                    <x-ui.select-dropdown
                        :label="__('personnel::vacations.labels.work_year')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model="openingSequence"
                        :model="$this->openingYearOptions"
                    />
                    @error('openingSequence') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="openingDays">{{ __('personnel::vacations.labels.opening_days') }}</x-label>
                    <x-livewire-input mode="gray" type="number" name="openingDays" wire:model="openingDays"></x-livewire-input>
                    @error('openingDays') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="openingNote">{{ __('personnel::vacations.labels.note') }}</x-label>
                    <x-livewire-input mode="gray" name="openingNote" wire:model="openingNote"></x-livewire-input>
                    @error('openingNote') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <x-button mode="black" wire:click="addOpening">{{ __('personnel::vacations.actions.add_opening') }}</x-button>
            </div>

            @if ($this->openingEntries->isNotEmpty())
                <ul class="divide-y divide-zinc-100 overflow-hidden rounded-xl bg-white ring-1 ring-inset ring-zinc-200/70">
                    @foreach ($this->openingEntries as $entry)
                        <li wire:key="opening-{{ $entry->id }}" class="flex items-center justify-between gap-3 px-3 py-2 text-[13px]">
                            <div class="min-w-0">
                                <p class="font-medium text-zinc-800">
                                    {{ $entry->workYear?->starts_on?->format('d.m.Y') }} – {{ $entry->workYear?->ends_on?->format('d.m.Y') }}
                                    · <span class="hrm-num">+{{ $entry->days }}</span> {{ __('personnel::vacations.labels.days') }}
                                </p>
                                <p class="text-[11px] text-ink-faint">{{ $entry->note ?: '—' }} · {{ $entry->author?->name ?? '—' }} · {{ $entry->created_at?->format('d.m.Y H:i') }}</p>
                            </div>
                            <x-action-button wire:click.prevent="confirmDeleteOpening({{ $entry->id }})" class="h-9 w-9 hover:bg-red-100" :title="__('personnel::common.actions.delete')">
                                <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                            </x-action-button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if ($this->legacyRows->isNotEmpty())
        <details class="rounded-2xl border border-zinc-200 px-4 py-3">
            <summary class="cursor-pointer text-[13px] font-medium text-zinc-600">{{ __('personnel::vacations.titles.legacy') }}</summary>
            <p class="mt-2 text-[12px] text-ink-faint">{{ __('personnel::vacations.hints.legacy') }}</p>
            <ul class="mt-2 space-y-1 text-[13px] text-zinc-700">
                @foreach ($this->legacyRows as $row)
                    <li wire:key="legacy-{{ $row->id }}" class="hrm-num">
                        {{ $row->year }}: {{ __('personnel::vacations.labels.legacy_row', ['total' => $row->vacation_days_total, 'remaining' => $row->remaining_days]) }}
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
