{{-- Admin → Məzuniyyət normaları: dörd qrup üzrə normalar (ƏM m.114–117, 119). --}}
<div class="flex flex-col gap-4">
    <div class="flex flex-col gap-1">
        <h1 class="text-[19px] font-semibold text-ink">{{ __('vacation::norms.title') }}</h1>
        <p class="max-w-3xl text-[13px] leading-5 text-ink-muted">{{ __('vacation::norms.subtitle') }}</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <nav class="flex flex-wrap gap-1 rounded-xl bg-[#f4f4f5] p-1" aria-label="{{ __('vacation::norms.title') }}">
            @foreach ($groups as $item)
                <button type="button" wire:key="norm-group-{{ $item }}" wire:click="selectGroup('{{ $item }}')"
                    @class([
                        'inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-[13px] font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                        'bg-white text-ink shadow-sm' => $group === $item,
                        'text-ink-muted hover:text-ink' => $group !== $item,
                    ])>
                    {{ __('vacation::norms.groups.'.$item) }}
                    <span class="rounded-md bg-zinc-100 px-1.5 text-[11px] text-zinc-500">{{ $counts[$item] ?? 0 }}</span>
                </button>
            @endforeach
        </nav>

        <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
            <x-icons.add-icon color="text-white" hover="text-zinc-50"></x-icons.add-icon>
            <span>{{ __('vacation::norms.actions.add') }}</span>
        </x-button>
    </div>

    <p class="rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted">{{ __('vacation::norms.hints.'.$group) }}</p>

    @if ($isAdded)
        <div wire:transition class="relative flex rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm">
            <x-action-button class="absolute right-2 top-2 h-9 w-9 hover:bg-zinc-100" wire:click="closeCrud()" :title="__('vacation::norms.actions.close')">
                <x-icons.close-icon></x-icons.close-icon>
            </x-action-button>

            <div class="mt-6 grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                @if (count($scopes) > 1)
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('vacation::norms.fields.scope')"
                            mode="default"
                            class="w-full"
                            :clearable="false"
                            wire:model.live="form.scope"
                            :model="collect($scopes)->map(fn ($scope) => ['id' => $scope, 'label' => __('vacation::norms.scopes.'.$scope)])->all()"
                        />
                        @error('form.scope') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if (($form['scope'] ?? null) === 'position')
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('vacation::norms.fields.position')"
                            placeholder="---"
                            mode="default"
                            class="w-full"
                            wire:model.live="form.position_id"
                            :model="$this->positionOptions()"
                            search-model="searchPosition"
                        />
                        @error('form.position_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if (($form['scope'] ?? null) === 'personnel')
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('vacation::norms.fields.personnel')"
                            placeholder="---"
                            mode="default"
                            class="w-full"
                            wire:model.live="form.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        />
                        @error('form.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if (($form['scope'] ?? null) === 'vtisk_category')
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('vacation::norms.fields.vtisk_category')"
                            mode="default"
                            class="w-full"
                            :clearable="false"
                            wire:model.live="form.condition"
                            :model="\App\Support\VtiskCategory::options()"
                        />
                        @error('form.condition') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if ($group === 'children')
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('vacation::norms.fields.condition')"
                            mode="default"
                            class="w-full"
                            :clearable="false"
                            wire:model.live="form.condition"
                            :model="[['id' => 'children_under_14', 'label' => __('vacation::norms.conditions.children_under_14')], ['id' => 'disabled_child', 'label' => __('vacation::norms.conditions.disabled_child')]]"
                        />
                        @error('form.condition') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if ($group === 'seniority' || ($group === 'children' && ($form['condition'] ?? null) === 'children_under_14'))
                    <div class="flex flex-col">
                        <x-label for="form.min_value">{{ __('vacation::norms.fields.min_value_'.$group) }}</x-label>
                        <x-livewire-input mode="default" type="number" name="form.min_value" wire:model="form.min_value"></x-livewire-input>
                        @error('form.min_value') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if ($group === 'seniority' || ($group === 'children' && ($form['condition'] ?? null) === 'disabled_child'))
                    <div class="flex flex-col">
                        <x-label for="form.max_value">{{ __('vacation::norms.fields.max_value_'.$group) }}</x-label>
                        <x-livewire-input mode="default" type="number" name="form.max_value" wire:model="form.max_value"></x-livewire-input>
                        @error('form.max_value') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                @if ($group === 'conditions')
                    <div class="flex flex-col">
                        <x-label for="form.valid_from">{{ __('vacation::norms.fields.valid_from') }}</x-label>
                        <x-ui.date-input wire:model="form.valid_from" name="form.valid_from" />
                        @error('form.valid_from') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="flex flex-col">
                        <x-label for="form.valid_to">{{ __('vacation::norms.fields.valid_to') }}</x-label>
                        <x-ui.date-input wire:model="form.valid_to" name="form.valid_to" />
                        @error('form.valid_to') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                @endif

                <div class="flex flex-col">
                    <x-label for="form.days">{{ __('vacation::norms.fields.days') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.days" wire:model="form.days"></x-livewire-input>
                    @error('form.days') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div class="flex flex-col">
                    <x-label for="form.legal_basis">{{ __('vacation::norms.fields.legal_basis') }}</x-label>
                    <x-livewire-input mode="default" name="form.legal_basis" wire:model="form.legal_basis" placeholder="ƏM m.114.3"></x-livewire-input>
                    @error('form.legal_basis') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div class="flex flex-col md:col-span-2">
                    <x-label for="form.note">{{ __('vacation::norms.fields.note') }}</x-label>
                    <x-livewire-input mode="default" name="form.note" wire:model="form.note"></x-livewire-input>
                    @error('form.note') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div class="flex flex-col justify-end gap-2 md:col-span-2">
                    @if ($group === 'base')
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="form.exclusive" class="rounded border-zinc-300 text-zinc-700 focus:ring-zinc-300">
                            <span class="text-sm text-zinc-700">{{ __('vacation::norms.fields.exclusive') }}</span>
                        </label>
                    @endif
                    @if ($group === 'conditions' && ($form['scope'] ?? null) === 'personnel')
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model.live="form.not_in_conditions" class="rounded border-zinc-300 text-zinc-700 focus:ring-zinc-300">
                            <span class="text-sm text-zinc-700">{{ __('vacation::norms.fields.not_in_conditions') }}</span>
                        </label>
                    @endif
                    @if ($group === 'children' && ($form['scope'] ?? null) !== 'personnel')
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="form.women_only" class="rounded border-zinc-300 text-zinc-700 focus:ring-zinc-300">
                            <span class="text-sm text-zinc-700">{{ __('vacation::norms.fields.women_only') }}</span>
                        </label>
                    @endif
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 text-zinc-700 focus:ring-zinc-300">
                        <span class="text-sm text-zinc-700">{{ __('vacation::norms.fields.is_active') }}</span>
                    </label>
                </div>

                <div class="flex items-end">
                    <x-button mode="black" wire:click="store">{{ __('vacation::norms.actions.save') }}</x-button>
                </div>
            </div>
        </div>
    @endif

    <div class="relative min-h-[240px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <x-table.tbl :headers="[__('vacation::norms.table.rule'), __('vacation::norms.table.days'), __('vacation::norms.table.legal_basis'), __('vacation::norms.table.status'), __('vacation::norms.table.actions')]">
                @forelse ($norms as $norm)
                    <tr wire:key="norm-{{ $norm->id }}">
                        <x-table.td style="white-space: normal !important;">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-ink">{{ $catalog->describe($norm) }}</span>
                                <div class="flex flex-wrap gap-1">
                                    @if ($norm->is_statutory)
                                        <x-small-badge mode="secondary">{{ __('vacation::norms.badges.statutory') }}</x-small-badge>
                                    @endif
                                    @if ($norm->exclusive)
                                        <x-small-badge mode="blue">{{ __('vacation::norms.badges.exclusive') }}</x-small-badge>
                                    @endif
                                </div>
                                @if (filled($norm->note))
                                    <span class="text-[12px] text-ink-faint">{{ $norm->note }}</span>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <span class="hrm-num text-sm font-semibold text-ink">{{ $group === 'base' ? '' : '+' }}{{ $norm->days }}</span>
                            <span class="text-[11px] text-ink-faint">{{ __('vacation::norms.days_suffix') }}</span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-[13px] text-ink-muted">{{ $norm->legal_basis ?? '—' }}</span>
                        </x-table.td>
                        <x-table.td>
                            <x-ui.toggle wire:click="toggle({{ $norm->id }})" :checked="$norm->is_active" aria-label="{{ __('vacation::norms.fields.is_active') }}" />
                        </x-table.td>
                        <x-table.td :isButton="true" width="100">
                            <div class="flex items-center space-x-2">
                                <x-action-button wire:click.prevent="openCrud({{ $norm->id }})" class="h-9 w-9 hover:bg-zinc-100" :title="__('vacation::norms.actions.edit')">
                                    <x-icons.edit-icon color="text-zinc-400" hover="text-zinc-500"></x-icons.edit-icon>
                                </x-action-button>
                                @unless ($norm->is_statutory)
                                    <x-action-button wire:click.prevent="confirmDelete({{ $norm->id }})" class="h-9 w-9 hover:bg-red-100" :title="__('vacation::norms.actions.delete')">
                                        <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                    </x-action-button>
                                @endunless
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state :title="__('vacation::norms.empty.title')" :message="__('vacation::norms.empty.'.$group)" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </div>
    </div>
</div>
