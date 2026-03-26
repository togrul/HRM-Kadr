    @if ($activeTab === 'cycles')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.cycle_setup')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    @if ($editingCycleId)
                        <div class="md:col-span-2">
                            <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <x-label for="cycle-name">{{ __('performance_evaluation::dashboard.fields.cycle_name') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-name" wire:model.defer="cycleForm.name" />
                        @error('cycleForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle_type')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-type" wire:model.live="cycleForm.cycle_type"
                            :model="collect(['annual','academic','quarterly'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.cycle_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                        @error('cycleForm.cycle_type') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-cycle-status" wire:model.live="cycleForm.status"
                            :model="collect(['draft','active','closed'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                        @error('cycleForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="cycle-period-start">{{ __('performance_evaluation::dashboard.fields.period_start') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-period-start" type="date" wire:model.defer="cycleForm.period_start" />
                        @error('cycleForm.period_start') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="cycle-period-end">{{ __('performance_evaluation::dashboard.fields.period_end') }}</x-label>
                        <x-livewire-input mode="gray" id="cycle-period-end" type="date" wire:model.defer="cycleForm.period_end" />
                        @error('cycleForm.period_end') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="cycle-description">{{ __('performance_evaluation::dashboard.fields.description') }}</x-label>
                        <textarea id="cycle-description" wire:model.defer="cycleForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('cycleForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="md:col-span-2 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="cycleForm.auto_generate_forms" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('performance_evaluation::dashboard.fields.auto_generate_forms') }}
                    </label>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-button mode="black" wire:click="storeCycle">{{ __('performance_evaluation::dashboard.actions.save_cycle') }}</x-button>
                            @if ($editingCycleId)
                                <x-button mode="secondary" wire:click="cancelCycleEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_cycles')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->recentCycles as $cycle)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $cycle->name }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $cycle->period_start?->format('d.m.Y') }} - {{ $cycle->period_end?->format('d.m.Y') }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <x-small-badge mode="green">{{ __('performance_evaluation::dashboard.statuses.'.$cycle->status) }}</x-small-badge>
                                    <span class="text-xs text-zinc-500">
                                        {{ $cycle->auto_generate_forms ? __('performance_evaluation::dashboard.labels.auto_generation_on') : __('performance_evaluation::dashboard.labels.auto_generation_off') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <x-ui.action-pill wire:click="editCycle({{ $cycle->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteCycle({{ $cycle->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.empty.recent_cycles') }}</p>
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    @endif

