@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];
    $selectedTemplate = collect($this->templateOptions())->firstWhere('id', $assignmentForm['template_id']);
    $toneClasses = [
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'muted' => 'border-zinc-200 bg-white text-zinc-700',
    ];
@endphp

<div class="flex flex-col gap-6 px-6 py-5">
    <div class="space-y-2">
        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding_admin.labels.kicker') }}</x-ui.field-label>
        <div class="flex flex-col gap-3 rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ $personnel->fullname }}</h2>
                <p class="text-sm text-zinc-500">{{ __('personnel::my_hr.onboarding_admin.messages.assignment_intro') }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.onboarding.summary.total') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.onboarding.summary.pending') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary['pending'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.onboarding.summary.acknowledged') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary['acknowledged'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.onboarding.summary.required') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary['required'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @if ($this->canAssignDocuments())
        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding_admin.labels.assignment_panel') }}</x-ui.field-label>
                    <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.onboarding_admin.titles.assign_document') }}</h3>
                    <p class="max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.onboarding_admin.messages.assignment_help') }}</p>
                </div>
                <a href="{{ route('onboarding-library') }}" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">
                    {{ __('personnel::my_hr.onboarding_admin.actions.open_library') }}
                </a>
            </div>

            <div class="mt-5 rounded-[24px] border border-zinc-200 bg-zinc-50/70 px-4 py-4 text-sm leading-6 text-zinc-600">
                {{ __('personnel::my_hr.onboarding_admin.messages.library_source') }}
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <x-ui.input-shell :label="__('personnel::my_hr.onboarding_admin.fields.template')" :error="$errors->first('assignmentForm.template_id')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.select-dropdown
                        :label="null"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="onboarding-template-picker"
                        wire:model.live="assignmentForm.template_id"
                        :model="$this->templateOptions()"
                        :selected-label="$selectedTemplate['label'] ?? null"
                        search-model="searchTemplate"
                    ></x-ui.select-dropdown>
                </x-ui.input-shell>

                <x-ui.input-shell :label="__('personnel::my_hr.onboarding_admin.fields.due_at')" :error="$errors->first('assignmentForm.due_at')" labelClass="tracking-tight text-zinc-500">
                    <x-ui.filter-input wire:model.live="assignmentForm.due_at" type="date" />
                </x-ui.input-shell>
            </div>

            <div class="mt-5 flex flex-wrap gap-3 border-t border-zinc-100 pt-4">
                <x-ui.async-button wire:click="assignTemplate" wire:target="assignTemplate" variant="primary">
                    {{ __('personnel::my_hr.onboarding_admin.actions.assign_document') }}
                </x-ui.async-button>
            </div>
        </div>
    @endif

    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding_admin.labels.current_assignments') }}</x-ui.field-label>
            <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.onboarding_admin.titles.current_assignments') }}</h3>
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
                                <span class="inline-flex items-center rounded-full bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)]">{{ $row['document_type_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium tracking-tight text-zinc-600">v{{ $row['version'] }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @if ($row['file_url'])
                                <a href="{{ $row['file_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">{{ __('personnel::my_hr.onboarding_admin.actions.open_template') }}</a>
                            @endif
                            @if ($this->canAssignDocuments())
                                <x-ui.async-button wire:click="waiveAssignment({{ $row['id'] }})" wire:target="waiveAssignment({{ $row['id'] }})" variant="secondary">
                                    {{ __('personnel::my_hr.onboarding_admin.actions.waive_assignment') }}
                                </x-ui.async-button>
                                <x-ui.async-button wire:click="removeAssignment({{ $row['id'] }})" wire:target="removeAssignment({{ $row['id'] }})" variant="danger">
                                    {{ __('personnel::my_hr.onboarding_admin.actions.remove_assignment') }}
                                </x-ui.async-button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding.labels.assigned_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['assigned_at'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding.labels.due_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['due_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding.labels.opened_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['opened_at'] ?: '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.onboarding.labels.acknowledged_at') }}</x-ui.field-label>
                            <p class="mt-2 text-sm font-semibold leading-6 text-zinc-900">{{ $row['acknowledged_at'] ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="icons.onboarding-library-icon" :title="__('personnel::my_hr.onboarding_admin.empty.title')" :message="__('personnel::my_hr.onboarding_admin.empty.body')" class="py-12" />
            @endforelse
        </div>
    </div>
</div>
