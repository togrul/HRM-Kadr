<div class="contents">
    @if ($tabelNo)
        @php $current = $this->currentAssignment; @endphp

        @if ($current)
            <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                <p class="hrm-eyebrow">{{ __('compensation::dashboard.assignments.current') }}</p>
                <p class="hrm-num mt-1.5 text-[26px] font-semibold leading-none tracking-[-0.035em] text-ink">
                    {{ $current->maskedBaseAmount() }}<span class="ml-1.5 text-[13px] font-medium text-ink-faint">{{ $current->currency }}</span>
                </p>
                <p class="hrm-num mt-1.5 text-[11.5px] text-ink-faint">
                    {{ __('compensation::dashboard.fields.effective_from') }}: {{ optional($current->effective_from)->format('d.m.Y') }}
                </p>
            </section>
        @endif

        @if ($canManage)
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.assignments.title') }}</h2>
                </div>

                <div class="space-y-4 px-4 py-3.5">
                    <div class="grid gap-3 lg:grid-cols-3">
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="assignmentForm.regime_id" :model="$this->regimeOptions" />
                            @error('assignmentForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.base_amount')" :error="$errors->first('assignmentForm.base_amount')">
                            <x-ui.input type="number" step="0.01" wire:model="assignmentForm.base_amount" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('assignmentForm.effective_from')">
                            <x-ui.input type="date" wire:model="assignmentForm.effective_from" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.order_no')">
                            <x-ui.input wire:model="assignmentForm.order_no" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="lg:col-span-2" :label="__('compensation::dashboard.fields.note')">
                            <x-ui.input wire:model="assignmentForm.note" />
                        </x-ui.input-shell>
                    </div>

                    <div class="border-t border-hairline-subtle pt-3.5">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="hrm-eyebrow">{{ __('compensation::dashboard.assignments.lines') }}</p>
                            <x-pill-button variant="primary" wire:click="addAssignmentLine">{{ __('compensation::dashboard.actions.add_line') }}</x-pill-button>
                        </div>

                        <div class="space-y-2">
                            @foreach ($assignmentLines as $i => $line)
                                <div wire:key="compensation-line-{{ $i }}" class="grid items-end gap-2 rounded-xl border border-hairline bg-[#fafafa] p-3 sm:grid-cols-12">
                                    <div class="min-w-0 sm:col-span-5">
                                        <x-ui.select-dropdown :label="__('compensation::dashboard.fields.component')" mode="gray" direction="auto" wire:model.live="assignmentLines.{{ $i }}.component_id" :model="$this->componentOptions" :instance="'line-'.$i" />
                                    </div>
                                    <x-ui.input-shell class="min-w-0 sm:col-span-3" :label="__('compensation::dashboard.fields.amount')">
                                        <x-ui.input type="number" step="0.01" wire:model="assignmentLines.{{ $i }}.amount" />
                                    </x-ui.input-shell>
                                    <x-ui.input-shell class="min-w-0 sm:col-span-3" :label="__('compensation::dashboard.fields.percent')">
                                        <x-ui.input type="number" step="0.01" wire:model="assignmentLines.{{ $i }}.percent" />
                                    </x-ui.input-shell>
                                    <div class="flex justify-end sm:col-span-1">
                                        <button type="button" wire:click="removeAssignmentLine({{ $i }})" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-pill-button variant="primary" wire:click="saveAssignment">{{ __('compensation::dashboard.actions.assign') }}</x-pill-button>
                    </div>
                </div>
            </section>
        @endif
    @endif
</div>
