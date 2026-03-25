@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];
    $selectedAsset = collect($this->assetOptions())->firstWhere('id', $assignmentForm['asset_id']);
    $statCards = [
        ['label' => __('personnel::my_hr.learning.summary.total'), 'value' => $summary['total']],
        ['label' => __('personnel::my_hr.learning.summary.pending'), 'value' => $summary['pending']],
        ['label' => __('personnel::my_hr.learning.summary.completed'), 'value' => $summary['completed']],
        ['label' => __('personnel::my_hr.learning.summary.required'), 'value' => $summary['required']],
    ];
    $toneClasses = [
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'muted' => 'border-zinc-200 bg-white text-zinc-700',
    ];
@endphp

<div class="flex flex-col gap-6 px-6 py-5">
    <div class="space-y-2">
        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning_admin.labels.kicker') }}</x-ui.field-label>
        <div class="flex flex-col gap-3 rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ $personnel->fullname }}</h2>
                <p class="text-sm text-zinc-500">{{ __('personnel::my_hr.learning_admin.messages.assignment_intro') }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($statCards as $card)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                        <x-ui.field-label as="div" class="tracking-tight">{{ $card['label'] }}</x-ui.field-label>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($this->canAssignContent())
        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning_admin.labels.assignment_panel') }}</x-ui.field-label>
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.learning_admin.titles.assign_asset') }}</h3>
                    <p class="max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.learning_admin.messages.assignment_help') }}</p>
                </div>
                <a href="{{ route('learning-library') }}" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                    {{ __('personnel::my_hr.learning_admin.actions.open_library') }}
                </a>
            </div>

            <div class="mt-5 rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-4 py-4 text-sm leading-6 text-zinc-600">
                {{ __('personnel::my_hr.learning_admin.messages.library_source') }}
            </div>

            <div class="mt-5 grid gap-4">
                <x-ui.input-shell :label="__('personnel::my_hr.learning_admin.fields.asset')" :error="$errors->first('assignmentForm.asset_id')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.select-dropdown
                        :label="null"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="learning-asset-picker"
                        wire:model.live="assignmentForm.asset_id"
                        :model="$this->assetOptions()"
                        :selected-label="$selectedAsset['label'] ?? null"
                        search-model="searchAsset"
                    ></x-ui.select-dropdown>
                </x-ui.input-shell>

                <x-ui.input-shell :label="__('personnel::my_hr.learning_admin.fields.due_at')" :error="$errors->first('assignmentForm.due_at')" labelClass="tracking-tight text-zinc-500">
                    <input wire:model.live="assignmentForm.due_at" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                </x-ui.input-shell>
            </div>

            <div class="mt-5 flex flex-wrap gap-3 border-t border-zinc-100 pt-4">
                <x-ui.async-button wire:click="assignAsset" wire:target="assignAsset" variant="primary">
                    {{ __('personnel::my_hr.learning_admin.actions.assign_asset') }}
                </x-ui.async-button>
            </div>
        </div>
    @endif

    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning_admin.labels.current_assignments') }}</x-ui.field-label>
            <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.learning_admin.titles.current_assignments') }}</h3>
        </div>

        <div class="mt-5 space-y-4">
            @forelse ($rows as $row)
                <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/60 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="inline-flex max-w-full rounded-[24px] border border-zinc-200 bg-white px-5 py-3">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $row['title'] }}</h3>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold tracking-tight {{ $toneClasses[$row['status_mode']] ?? $toneClasses['muted'] }}">{{ $row['status_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700">{{ $row['content_type_label'] }}</span>
                                @if ($row['estimated_minutes'])
                                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium tracking-tight text-zinc-600">{{ $row['estimated_minutes'] }} {{ __('personnel::my_hr.learning.labels.minutes') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @if ($row['content_url'])
                                <a href="{{ $row['content_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300">{{ __('personnel::my_hr.learning_admin.actions.open_asset') }}</a>
                            @endif
                            @if ($this->canAssignContent())
                                <x-ui.async-button wire:click="waiveAssignment({{ $row['id'] }})" wire:target="waiveAssignment({{ $row['id'] }})" variant="secondary">
                                    {{ __('personnel::my_hr.learning_admin.actions.waive_assignment') }}
                                </x-ui.async-button>
                                <x-ui.async-button wire:click="removeAssignment({{ $row['id'] }})" wire:target="removeAssignment({{ $row['id'] }})" variant="danger">
                                    {{ __('personnel::my_hr.learning_admin.actions.remove_assignment') }}
                                </x-ui.async-button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.assigned_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['assigned_at'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.due_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['due_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.opened_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['opened_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.learning.labels.completed_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['completed_at'] ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[28px] border border-dashed border-zinc-300 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.learning.empty.title') }}</h3>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.learning.empty.body') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
