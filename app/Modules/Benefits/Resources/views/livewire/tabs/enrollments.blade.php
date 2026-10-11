@php
    $check = 'rounded border-hairline text-ink focus:ring-[#e4e4e7]';
    $checkLabel = 'inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted';
    $statusMode = ['active' => 'green', 'ended' => 'secondary', 'cancelled' => 'rose'];
@endphp

<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.enrollments.title') }}</h2>
            <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                <div class="sm:w-[220px]">
                    <x-ui.input icon="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('benefits::dashboard.actions.search_personnel') }}" />
                </div>
                <div class="sm:w-[200px]">
                    <x-ui.select wire:model.live="planFilter">
                        <option value="">{{ __('benefits::dashboard.filters.all_plans') }}</option>
                        @foreach ($this->planOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="sm:w-[160px]">
                    <x-ui.select wire:model.live="statusFilter">
                        <option value="">{{ __('benefits::dashboard.filters.all_statuses') }}</option>
                        @foreach (['active', 'ended', 'cancelled'] as $status)
                            <option value="{{ $status }}">{{ __('benefits::dashboard.statuses.'.$status) }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>
        </div>

        <x-table.tbl :headers="[
            __('benefits::dashboard.columns.personnel'),
            __('benefits::dashboard.columns.plan'),
            __('benefits::dashboard.columns.period'),
            __('benefits::dashboard.columns.employee_amount'),
            __('benefits::dashboard.columns.employer_amount'),
            __('benefits::dashboard.columns.status'),
            __('benefits::dashboard.columns.actions'),
        ]">
            @forelse ($this->enrollments as $enrollment)
                <tr wire:key="benefits-enrollment-{{ $enrollment->id }}">
                    <x-table.td standart-width>
                        <p class="max-w-[220px] truncate text-[13px] font-medium text-ink">{{ trim(($enrollment->personnel?->surname ?? '').' '.($enrollment->personnel?->name ?? '')) ?: '—' }}</p>
                        <p class="hrm-num text-[11px] text-ink-faint">{{ $enrollment->tabel_no }}</p>
                    </x-table.td>
                    <x-table.td>
                        <p class="max-w-[200px] truncate text-[13px] text-ink">{{ $enrollment->plan?->name }}</p>
                        @if ($enrollment->dependents_count > 0)
                            <p class="text-[11px] text-ink-faint">{{ __('benefits::dashboard.enrollments.dependents_count', ['count' => $enrollment->dependents_count]) }}</p>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[12.5px] text-ink-soft">{{ $enrollment->start_date?->format('d.m.Y') }} – {{ $enrollment->end_date?->format('d.m.Y') ?? '…' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[13px] text-ink">{{ $money($enrollment->employee_amount) }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[13px] text-ink">{{ $money($enrollment->employer_amount) }}</span>
                    </x-table.td>
                    <x-table.td>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge :mode="$statusMode[$enrollment->status] ?? 'secondary'" dot>{{ __('benefits::dashboard.statuses.'.$enrollment->status) }}</x-small-badge>
                            @if ($enrollment->eligibility_overridden)
                                <x-small-badge mode="amber">{{ __('benefits::dashboard.enrollments.overridden') }}</x-small-badge>
                            @endif
                            @if ($enrollment->end_reason === 'termination')
                                <x-small-badge mode="secondary">{{ __('benefits::dashboard.end_reasons.termination') }}</x-small-badge>
                            @endif
                        </div>
                    </x-table.td>
                    <x-table.td :isButton="true">
                        @if ($canManage && $enrollment->status === 'active')
                            <div class="flex items-center justify-end gap-1">
                                @if ($enrollment->plan?->allows_dependents)
                                    <button type="button" wire:click="editDependents({{ $enrollment->id }})" title="{{ __('benefits::dashboard.actions.dependents') }}" class="{{ $editBtn }}">
                                        <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    </button>
                                @endif
                                <button type="button" wire:click="startEnd({{ $enrollment->id }})" title="{{ __('benefits::dashboard.actions.end') }}" class="{{ $editBtn }}">
                                    <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l6-4"/></svg>
                                </button>
                                <button type="button" x-on:click="{{ $confirm('cancelEnrollment('.$enrollment->id.')', 'cancel_enrollment', 'cancel_enrollment') }}" title="{{ __('benefits::dashboard.actions.cancel_enrollment') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            </div>
                        @endif
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10">
                        <x-ui.empty-state icon="icons.gift-icon" :title="__('benefits::dashboard.enrollments.empty')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>

        <x-pagination :paginator="$this->enrollments" :unit="__('benefits::dashboard.summary.enrollments')" />
    </section>

    {{-- ===================== side panels ===================== --}}
    @if ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="benefits-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('benefits::dashboard.actions.close')"
            width="3xl"
        >
            @include('benefits::livewire.tabs.partials.panel-header', ['panelTitle' => __('benefits::dashboard.enrollments.panel_'.$panel)])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'enroll')
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <p class="text-[12px] font-medium text-ink-muted">{{ __('benefits::dashboard.fields.personnel') }}</p>
                        @if ($selectedTabelNo)
                            <div class="mt-1.5 flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                                <span class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $selectedPersonnelLabel }}</span>
                                <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">{{ __('benefits::dashboard.actions.clear') }}</button>
                            </div>
                        @else
                            <div class="mt-1.5">
                                <x-ui.input icon="search" wire:model.live.debounce.300ms="personnelSearch" x-on:focus="open = true" placeholder="{{ __('benefits::dashboard.actions.search_personnel') }}" />
                            </div>
                            @if (count($this->personnelResults))
                                <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-card">
                                    @foreach ($this->personnelResults as $result)
                                        <button
                                            type="button"
                                            wire:key="benefits-personnel-{{ $result['tabel_no'] }}"
                                            wire:click="selectPersonnel({{ \Illuminate\Support\Js::from($result['tabel_no']) }}, {{ \Illuminate\Support\Js::from($result['label']) }})"
                                            x-on:click="open = false"
                                            class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                                        >{{ $result['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.plan')" :error="$errors->first('enrollForm.plan_id') ?: $errors->first('plan')">
                            <x-ui.select wire:model.live="enrollForm.plan_id">
                                <option value="">—</option>
                                @foreach ($this->planOptions->where('is_active', true) as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.start_date')" :error="$errors->first('enrollForm.start_date')">
                            <x-ui.input type="date" wire:model.live="enrollForm.start_date" />
                        </x-ui.input-shell>
                    </div>

                    @php $reasons = $this->eligibilityReasons; @endphp
                    @if ($reasons !== null)
                        @if ($reasons === [])
                            <p class="rounded-[10px] border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12.5px] text-emerald-800">{{ __('benefits::dashboard.eligibility.eligible') }}</p>
                        @else
                            <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-3 py-2.5 text-[12.5px] text-amber-900">
                                <p class="font-medium">{{ __('benefits::dashboard.eligibility.not_eligible') }}</p>
                                <ul class="mt-1 list-disc pl-5">
                                    @foreach ($reasons as $reason)
                                        <li>{{ __('benefits::dashboard.eligibility.reasons.'.$reason) }}</li>
                                    @endforeach
                                </ul>
                                <label class="{{ $checkLabel }} mt-2">
                                    <input type="checkbox" wire:model.live="enrollForm.override" class="{{ $check }}" />
                                    {{ __('benefits::dashboard.fields.override') }}
                                </label>
                            </div>
                        @endif
                    @endif

                    @if ($selectedTabelNo && $this->kinshipOptions !== [])
                        <div>
                            <p class="text-[12px] font-medium text-ink-muted">{{ __('benefits::dashboard.fields.dependents') }}</p>
                            <div class="mt-2 space-y-1.5">
                                @foreach ($this->kinshipOptions as $kin)
                                    <label wire:key="benefits-kin-{{ $kin['id'] }}" class="{{ $checkLabel }}">
                                        <input type="checkbox" value="{{ $kin['id'] }}" wire:model="enrollForm.kinship_ids" class="{{ $check }}" />
                                        {{ $kin['full_name'] }}@if ($kin['relation']) · {{ $kin['relation'] }}@endif
                                    </label>
                                @endforeach
                            </div>
                            @error('dependents') <p class="mt-1 text-[12px] text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <x-ui.input-shell :label="__('benefits::dashboard.fields.note')" :error="$errors->first('enrollForm.note') ?: $errors->first('note')">
                        <x-ui.input wire:model="enrollForm.note" />
                    </x-ui.input-shell>
                @elseif ($panel === 'dependents')
                    <div class="space-y-1.5">
                        @forelse ($this->kinshipOptions as $kin)
                            <label wire:key="benefits-dep-{{ $kin['id'] }}" class="{{ $checkLabel }}">
                                <input type="checkbox" value="{{ $kin['id'] }}" wire:model="dependentIds" class="{{ $check }}" />
                                {{ $kin['full_name'] }}@if ($kin['relation']) · {{ $kin['relation'] }}@endif
                            </label>
                        @empty
                            <p class="text-[12.5px] text-ink-faint">{{ __('benefits::dashboard.enrollments.no_kinship') }}</p>
                        @endforelse
                        @error('dependents') <p class="mt-1 text-[12px] text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @elseif ($panel === 'end')
                    <x-ui.input-shell :label="__('benefits::dashboard.fields.end_date')" :error="$errors->first('endDate') ?: $errors->first('end_date')">
                        <x-ui.input type="date" wire:model="endDate" />
                    </x-ui.input-shell>
                @endif
            </div>

            @include('benefits::livewire.tabs.partials.panel-footer', ['save' => match ($panel) {
                'dependents' => 'saveDependents',
                'end' => 'saveEnd',
                default => 'saveEnrollment',
            }])
        </x-ui.side-panel>
    @endif
</div>
