@php
    $raterTypes = ['manager', 'peer', 'subordinate', 'self'];
    $statusStyles = [
        'collecting' => 'bg-sky-50 text-sky-700 border-sky-200',
        'calibrating' => 'bg-amber-50 text-amber-700 border-amber-200',
        'closed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
@endphp

<div class="flex flex-col gap-4 px-6 py-4" wire:key="feedback-360-{{ $section }}-{{ $activeRequestId }}">
    {{-- ───────────── stats ───────────── --}}
    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase text-zinc-500">{{ __('performance_evaluation::feedback.stats.requests') }}</p>
            <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->summary['requests'] }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::feedback.stats.collecting') }}</p>
            <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->summary['collecting'] }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::feedback.stats.calibrating') }}</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->summary['calibrating'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('performance_evaluation::feedback.stats.closed') }}</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->summary['closed'] }}</p>
        </div>
    </div>

    {{-- ═════════════ LIST ═════════════ --}}
    @if ($section === 'list')
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <x-ui.filter-native-select wire:model.live="cycleId">
                        @foreach ($this->cycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                        @endforeach
                    </x-ui.filter-native-select>
                    <span class="text-xs text-zinc-500">{{ __('performance_evaluation::feedback.subtitle') }}</span>
                </div>
                @can('manage-performance-evaluation')
                    <button type="button" wire:click="openCreate" class="h-10 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">
                        {{ __('performance_evaluation::feedback.actions.new_request') }}
                    </button>
                @endcan
            </div>

            <x-table.tbl :headers="[
                __('performance_evaluation::feedback.fields.subject'),
                __('performance_evaluation::feedback.fields.template'),
                __('performance_evaluation::feedback.fields.progress'),
                __('performance_evaluation::feedback.fields.status'),
                __('performance_evaluation::feedback.fields.final_score'),
                __('performance_evaluation::feedback.fields.actions'),
            ]">
                @forelse ($this->requests as $request)
                    <tr>
                        <x-table.td>
                            <span class="text-sm font-semibold text-zinc-800">{{ $request->subject?->fullname ?? '—' }}</span>
                            <span class="block text-xs text-zinc-400">{{ $request->cycle?->name }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-sm text-zinc-600">{{ $request->template?->name ?? '—' }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-sm font-medium text-zinc-700">{{ $request->submitted_raters_count }} / {{ $request->raters_count }}</span>
                            <span class="block text-xs text-zinc-400">{{ __('performance_evaluation::feedback.fields.raters') }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$request->status] ?? 'bg-zinc-50 text-zinc-600 border-zinc-200' }}">
                                {{ __('performance_evaluation::feedback.status.'.$request->status) }}
                            </span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-sm font-semibold text-zinc-800">{{ $request->final_score !== null ? number_format((float) $request->final_score, 1) : '—' }}</span>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" wire:click="openDetail({{ $request->id }})" class="rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">
                                    {{ __('performance_evaluation::feedback.actions.open') }}
                                </button>
                                @can('manage-performance-evaluation')
                                    <button type="button" wire:click="openCalibrate({{ $request->id }})" class="rounded-lg border border-amber-200 px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                        {{ __('performance_evaluation::feedback.actions.calibrate') }}
                                    </button>
                                    @if ($request->status === 'closed')
                                        <button type="button" wire:click="reopenRequest({{ $request->id }})" class="rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-50">
                                            {{ __('performance_evaluation::feedback.actions.reopen') }}
                                        </button>
                                    @endif
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('performance_evaluation::feedback.confirm.delete')), confirmText: @js(__('performance_evaluation::feedback.actions.delete')), run: () => $wire.deleteRequest({{ $request->id }}) })"
                                        class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                        {{ __('performance_evaluation::feedback.actions.delete') }}
                                    </button>
                                @endcan
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="flex items-center justify-center py-8">
                                <span class="text-sm font-medium text-zinc-400">{{ __('performance_evaluation::feedback.empty.requests') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>
    @endif

    {{-- ═════════════ DETAIL ═════════════ --}}
    @if ($section === 'detail' && $this->activeRequest)
        @php($request = $this->activeRequest)
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <button type="button" wire:click="backToList" class="text-xs font-medium text-zinc-400 hover:text-zinc-600">← {{ __('performance_evaluation::feedback.actions.back') }}</button>
                    <h2 class="mt-1 text-xl font-semibold text-zinc-900">{{ $request->subject?->fullname }}</h2>
                    <p class="text-xs text-zinc-500">{{ $request->cycle?->name }} · {{ $request->template?->name }}
                        @if ($request->is_anonymous) · <span class="text-zinc-400">{{ __('performance_evaluation::feedback.fields.anonymous') }}</span> @endif
                    </p>
                </div>
                @can('manage-performance-evaluation')
                    <button type="button" wire:click="openCalibrate({{ $request->id }})" class="h-10 rounded-xl bg-amber-500 px-5 text-sm font-semibold text-white shadow-sm hover:bg-amber-400 active:scale-[0.98]">
                        {{ __('performance_evaluation::feedback.actions.go_calibrate') }}
                    </button>
                @endcan
            </div>

            {{-- add rater --}}
            @can('manage-performance-evaluation')
                <div class="mb-4 rounded-xl border border-dashed border-zinc-200 bg-zinc-50 px-3 py-3">
                    <p class="mb-2 text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::feedback.actions.add_rater') }}</p>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="w-44">
                            <x-label value="{{ __('performance_evaluation::feedback.fields.rater_type') }}" />
                            <x-ui.filter-native-select wire:model="raterForm.rater_type">
                                @foreach ($raterTypes as $type)
                                    <option value="{{ $type }}">{{ __('performance_evaluation::feedback.rater_types.'.$type) }}</option>
                                @endforeach
                            </x-ui.filter-native-select>
                        </div>
                        <div class="min-w-[16rem] flex-1">
                            <x-label value="{{ __('performance_evaluation::feedback.fields.rater') }}" />
                            <livewire:performance-evaluation.personnel-picker
                                target="rater"
                                :selectedId="$raterForm['rater_personnel_id']"
                                :selectedLabel="$raterForm['rater_label']"
                                :placeholder="__('performance_evaluation::feedback.fields.rater_search')"
                                wire:key="rater-picker-{{ $activeRequestId }}" />
                            @error('raterForm.rater_personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <button type="button" wire:click="addRater" class="h-10 rounded-xl bg-zinc-900 px-5 text-sm font-semibold text-white hover:bg-zinc-700">
                            {{ __('performance_evaluation::feedback.actions.add') }}
                        </button>
                    </div>
                </div>
            @endcan

            {{-- raters list --}}
            <x-table.tbl :headers="[
                __('performance_evaluation::feedback.fields.rater'),
                __('performance_evaluation::feedback.fields.rater_type'),
                __('performance_evaluation::feedback.fields.status'),
                __('performance_evaluation::feedback.fields.submitted_at'),
                __('performance_evaluation::feedback.fields.actions'),
            ]">
                @forelse ($request->raters as $rater)
                    <tr>
                        <x-table.td>
                            <span class="text-sm font-medium text-zinc-800">{{ $rater->personnel?->fullname ?? '—' }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700">{{ __('performance_evaluation::feedback.rater_types.'.$rater->rater_type) }}</span>
                        </x-table.td>
                        <x-table.td>
                            @if ($rater->status === 'submitted')
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">{{ __('performance_evaluation::feedback.rater_status.submitted') }}</span>
                            @else
                                <span class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-0.5 text-xs font-medium text-zinc-500">{{ __('performance_evaluation::feedback.rater_status.pending') }}</span>
                            @endif
                        </x-table.td>
                        <x-table.td>
                            <span class="text-xs text-zinc-500">{{ $rater->submitted_at?->format('d.m.Y H:i') ?? '—' }}</span>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <div class="flex items-center justify-end gap-1.5">
                                @can('manage-performance-evaluation')
                                    <button type="button" wire:click="openScoring({{ $rater->id }})" class="rounded-lg border border-sky-200 px-2.5 py-1 text-xs font-medium text-sky-700 hover:bg-sky-50">
                                        {{ __('performance_evaluation::feedback.actions.enter_scores') }}
                                    </button>
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('performance_evaluation::feedback.confirm.remove_rater')), confirmText: @js(__('performance_evaluation::feedback.actions.remove')), run: () => $wire.removeRater({{ $rater->id }}) })"
                                        class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                        {{ __('performance_evaluation::feedback.actions.remove') }}
                                    </button>
                                @endcan
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="flex items-center justify-center py-6">
                                <span class="text-sm font-medium text-zinc-400">{{ __('performance_evaluation::feedback.empty.raters') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>
    @endif

    {{-- ═════════════ CALIBRATE ═════════════ --}}
    @if ($section === 'calibrate' && $this->activeRequest)
        @php($request = $this->activeRequest)
        @php($aggregate = $this->aggregate)
        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <button type="button" wire:click="backToList" class="text-xs font-medium text-zinc-400 hover:text-zinc-600">← {{ __('performance_evaluation::feedback.actions.back') }}</button>
                    <h2 class="mt-1 text-xl font-semibold text-zinc-900">{{ __('performance_evaluation::feedback.calibrate.title') }} — {{ $request->subject?->fullname }}</h2>
                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::feedback.calibrate.description') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('performance_evaluation::feedback.calibrate.raw_final') }}</p>
                    <p class="text-2xl font-semibold text-zinc-800">{{ $aggregate['raw_final'] !== null ? number_format($aggregate['raw_final'], 1) : '—' }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-[11px] uppercase text-zinc-400">
                            <th class="px-2 py-2 text-left">{{ __('performance_evaluation::feedback.fields.criterion') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.rater_types.manager') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.rater_types.peer') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.rater_types.subordinate') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.rater_types.self') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.calibrate.raw_avg') }}</th>
                            <th class="px-2 py-2 text-center">{{ __('performance_evaluation::feedback.calibrate.calibrated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aggregate['items'] as $item)
                            <tr class="border-b border-zinc-100">
                                <td class="px-2 py-2">
                                    <span class="font-medium text-zinc-800">{{ $item['name'] }}</span>
                                    <span class="block text-xs text-zinc-400">{{ $item['section'] }} · {{ rtrim(rtrim(number_format($item['weight'], 1), '0'), '.') }}%</span>
                                </td>
                                @foreach (['manager', 'peer', 'subordinate', 'self'] as $type)
                                    <td class="px-2 py-2 text-center text-zinc-600">{{ $item['by_type'][$type] !== null ? number_format($item['by_type'][$type], 1) : '·' }}</td>
                                @endforeach
                                <td class="px-2 py-2 text-center font-semibold text-zinc-800">{{ $item['average'] !== null ? number_format($item['average'], 1) : '·' }}</td>
                                <td class="px-2 py-2 text-center">
                                    @can('manage-performance-evaluation')
                                        <input type="number" min="0" max="100" step="0.5"
                                            wire:model="calibrationInputs.{{ $item['id'] }}"
                                            @disabled($request->status === 'closed')
                                            class="w-20 rounded-lg border border-zinc-200 px-2 py-1 text-center text-sm focus:border-amber-400 focus:ring-amber-400 disabled:bg-zinc-100" />
                                    @else
                                        {{ data_get($calibrationInputs, $item['id']) }}
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-sm text-zinc-400">{{ __('performance_evaluation::feedback.empty.scores') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <x-label value="{{ __('performance_evaluation::feedback.calibrate.note') }}" />
                <x-textarea mode="gray" name="calibrationNote" wire:model="calibrationNote" @disabled($request->status === 'closed')></x-textarea>
            </div>

            @can('manage-performance-evaluation')
                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                    @if ($request->status === 'closed')
                        <span class="mr-auto text-sm font-medium text-emerald-700">{{ __('performance_evaluation::feedback.calibrate.approved_final') }}: {{ number_format((float) $request->final_score, 1) }}</span>
                        <button type="button" wire:click="reopenRequest({{ $request->id }})" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::feedback.actions.reopen') }}</button>
                    @else
                        <button type="button" wire:click="saveCalibration(false)" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::feedback.actions.save_draft') }}</button>
                        <button type="button" wire:click="saveCalibration(true)" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::feedback.actions.approve') }}</button>
                    @endif
                </div>
            @endcan
        </div>
    @endif

    {{-- ───────────── side modal: create / scoring ───────────── --}}
    @can('manage-performance-evaluation')
        <x-side-modal size="large">
            @if ($showSideMenu === 'create')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::feedback.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::feedback.actions.new_request') }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <x-label value="{{ __('performance_evaluation::feedback.fields.cycle') }}" />
                            <x-ui.filter-native-select wire:model="createForm.performance_cycle_id">
                                <option value="">—</option>
                                @foreach ($this->cycles as $cycle)
                                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                                @endforeach
                            </x-ui.filter-native-select>
                            @error('createForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::feedback.fields.template') }}" />
                            <x-ui.filter-native-select wire:model="createForm.performance_form_template_id">
                                <option value="">—</option>
                                @foreach ($this->templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </x-ui.filter-native-select>
                            @error('createForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::feedback.fields.subject') }}" />
                            <livewire:performance-evaluation.personnel-picker
                                target="subject"
                                :selectedId="$createForm['subject_personnel_id']"
                                :selectedLabel="$createForm['subject_label']"
                                :placeholder="__('performance_evaluation::feedback.fields.subject_search')"
                                wire:key="subject-picker" />
                            @error('createForm.subject_personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <x-checkbox name="createForm.is_anonymous" model="createForm.is_anonymous"></x-checkbox>
                            <x-label class="mb-0" value="{{ __('performance_evaluation::feedback.fields.anonymous') }}" />
                        </div>
                        <div>
                            <x-label value="{{ __('performance_evaluation::feedback.fields.due_date') }}" />
                            <x-pikaday-input mode="gray" name="createForm.due_date" format="Y-MM-DD" wire:model="createForm.due_date">
                                <x-slot name="script">
                                    $el.onchange = function () { @this.set('createForm.due_date', $el.value); }
                                </x-slot>
                            </x-pikaday-input>
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::feedback.actions.cancel') }}</button>
                        <button type="button" wire:click="saveRequest" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98]">{{ __('performance_evaluation::feedback.actions.save') }}</button>
                    </div>
                </div>
            @endif

            @if ($showSideMenu === 'scoring')
                <div class="flex h-full flex-col">
                    <div class="mb-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-zinc-400">{{ __('performance_evaluation::feedback.eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-semibold tracking-tight text-zinc-950">{{ __('performance_evaluation::feedback.actions.enter_scores') }}</h2>
                    </div>
                    <div class="flex-1 space-y-4 overflow-y-auto">
                        @forelse ($this->templateItems as $item)
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50/60 px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-sm font-medium text-zinc-800">{{ $item['name'] }}</span>
                                        <span class="block text-xs text-zinc-400">{{ $item['section'] }}</span>
                                    </div>
                                    <input type="number" min="0" max="100" step="0.5"
                                        wire:model="scoreInputs.{{ $item['id'] }}"
                                        class="w-24 rounded-lg border border-zinc-200 px-2 py-1.5 text-center text-sm focus:border-sky-400 focus:ring-sky-400" />
                                </div>
                                <input type="text" wire:model="commentInputs.{{ $item['id'] }}"
                                    placeholder="{{ __('performance_evaluation::feedback.fields.comment') }}"
                                    class="mt-2 w-full rounded-lg border border-zinc-200 px-2 py-1.5 text-sm focus:border-sky-400 focus:ring-sky-400" />
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-zinc-400">{{ __('performance_evaluation::feedback.empty.items') }}</p>
                        @endforelse
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-zinc-100 pt-5">
                        <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-medium text-zinc-600 hover:bg-zinc-50">{{ __('performance_evaluation::feedback.actions.cancel') }}</button>
                        <button type="button" wire:click="saveScores" class="h-11 rounded-xl bg-sky-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-sky-500 active:scale-[0.98]">{{ __('performance_evaluation::feedback.actions.save') }}</button>
                    </div>
                </div>
            @endif
        </x-side-modal>
    @endcan
</div>
