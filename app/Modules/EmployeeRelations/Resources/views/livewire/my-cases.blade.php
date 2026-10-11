@php
    $labelClass = 'hrm-eyebrow block pb-1';
    $reported = $this->reported;
    $aboutMe = $this->aboutMe;
@endphp

<div class="flex flex-col gap-4">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-2 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.my.title') }}</h2>
                <p class="mt-0.5 max-w-2xl text-[12px] leading-5 text-ink-faint">{{ __('employee_relations::cases.my.description') }}</p>
            </div>
            @if ($this->canSubmit && ! $showForm)
                <x-pill-button variant="primary" wire:click="openForm">{{ __('employee_relations::cases.my.actions.new') }}</x-pill-button>
            @endif
        </div>

        @if ($showForm)
            <div class="grid gap-3 p-4 sm:grid-cols-2" wire:key="er-my-form">
                <x-ui.input-shell :label="__('employee_relations::cases.fields.type')" :error="$errors->first('form.case_type_id') ?: $errors->first('case_type_id')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.select wire:model="form.case_type_id">
                        @foreach ($this->typeOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.title')" :error="$errors->first('form.title')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.input wire:model="form.title" type="text" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.description')" :error="$errors->first('form.description')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                    <x-ui.textarea wire:model="form.description" :rows="5" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.subject_description')" :hint="__('employee_relations::cases.my.subject_hint')" :error="$errors->first('form.subject_description')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.input wire:model="form.subject_description" type="text" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.occurred_at')" :error="$errors->first('form.occurred_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                    <x-ui.input wire:model="form.occurred_at" type="date" class="hrm-num" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('employee_relations::cases.fields.evidence_file')" :hint="__('employee_relations::cases.hints.evidence_file')" :error="$errors->first('attachment')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                    <input wire:model="attachment" type="file"
                        class="block min-h-10 w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-1.5 text-base text-ink-muted shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-ink file:px-3 file:py-1 file:text-[14px] file:font-semibold file:text-white sm:text-sm" />
                </x-ui.input-shell>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <x-checkbox name="form.is_anonymous" model="form.is_anonymous">{{ __('employee_relations::cases.my.anonymous') }}</x-checkbox>
                    <p class="text-[11.5px] leading-5 text-ink-faint">{{ __('employee_relations::cases.my.anonymous_hint') }}</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-hairline-subtle px-4 py-3">
                <x-pill-button variant="ghost" wire:click="closeForm">{{ __('employee_relations::cases.actions.cancel') }}</x-pill-button>
                <x-pill-button variant="primary" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,attachment">{{ __('employee_relations::cases.my.actions.submit') }}</x-pill-button>
            </div>
        @endif
    </section>

    @foreach (['reported' => $reported, 'about_me' => $aboutMe] as $section => $rows)
        <section class="overflow-hidden rounded-xl border border-hairline bg-white" wire:key="er-my-{{ $section }}">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.my.sections.'.$section) }}</h2>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($rows as $row)
                    <div class="flex flex-col gap-1.5 px-4 py-3" wire:key="er-my-{{ $section }}-{{ $row['id'] }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="hrm-num text-[12px] text-ink-faint">{{ $row['number'] }}</span>
                            <span class="text-[13px] font-medium text-ink">{{ $row['title'] }}</span>
                            <x-small-badge mode="secondary" dot>{{ $row['status'] }}</x-small-badge>
                            @if ($section === 'reported' && $row['anonymous'])
                                <x-small-badge mode="violet">{{ __('employee_relations::cases.labels.anonymous') }}</x-small-badge>
                            @endif
                        </div>
                        <p class="text-[12px] text-ink-faint">{{ $row['type'] }}</p>

                        @if ($section === 'reported' && $row['result'])
                            <p class="text-[12.5px] text-ink-soft">{{ $row['result'] }}</p>
                        @endif

                        @if ($section === 'about_me')
                            <p class="text-[12.5px] text-ink"><span class="font-medium">{{ $row['outcome'] }}</span> · <span class="hrm-num">{{ $row['decided_at'] }}</span></p>
                            @if ($row['summary'])
                                <p class="whitespace-pre-line text-[12.5px] leading-5 text-ink-soft">{{ $row['summary'] }}</p>
                            @endif
                        @endif

                        @if ($row['appeal'])
                            <p class="text-[12px] text-ink-muted">{{ __('employee_relations::cases.my.appeal_state', ['status' => $row['appeal']['status']]) }}@if ($row['appeal']['outcome']) — {{ $row['appeal']['outcome'] }}@endif</p>
                        @elseif ($row['can_appeal'])
                            @if ($appealCaseId === $row['id'])
                                <div class="mt-1 flex flex-col gap-2">
                                    <x-ui.textarea wire:model="appealReason" :rows="3" :placeholder="__('employee_relations::cases.fields.appeal_reason')" />
                                    @error('appealReason') <x-validation>{{ $message }}</x-validation> @enderror
                                    <div class="flex gap-2">
                                        <x-pill-button variant="primary" wire:click="submitAppeal" wire:loading.attr="disabled" wire:target="submitAppeal">{{ __('employee_relations::cases.my.actions.submit_appeal') }}</x-pill-button>
                                        <x-pill-button variant="ghost" wire:click="cancelAppeal">{{ __('employee_relations::cases.actions.cancel') }}</x-pill-button>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-pill-button wire:click="startAppeal({{ $row['id'] }})">{{ __('employee_relations::cases.my.actions.appeal') }}</x-pill-button>
                                    <span class="hrm-num text-[11.5px] text-ink-faint">{{ __('employee_relations::cases.my.appeal_until', ['date' => $row['appeal_deadline']]) }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-[12.5px] text-ink-faint">{{ __('employee_relations::cases.my.empty.'.$section) }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
