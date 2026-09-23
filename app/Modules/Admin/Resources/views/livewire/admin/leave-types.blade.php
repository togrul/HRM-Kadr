<div class="flex flex-col">
    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-zinc-50"></x-icons.add-icon>
                <span>{{ __('admin::leave_types.actions.add') }}</span>
            </x-button>
        </div>
    </div>

    @if ($isAdded)
        <div wire:transition class="flex border border-zinc-300 rounded-md bg-zinc-50 relative px-3 py-2 my-3">
            <button type="button" class="appearance-none absolute top-2 right-2 flex h-10 w-10 items-center justify-center rounded-lg text-ink-muted hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400" aria-label="{{ __('admin::references.actions.close') }}" title="{{ __('admin::references.actions.close') }}" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2 mt-4 w-full">
                <div class="flex flex-col">
                    <x-label for="form.name">{{ __('admin::leave_types.fields.name') }}</x-label>
                    <x-livewire-input mode="default" name="form.name" wire:model="form.name"></x-livewire-input>
                    @error('form.name')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.attendance_code">{{ __('admin::leave_types.fields.attendance_code') }}</x-label>
                    <x-livewire-input
                        mode="default"
                        name="form.attendance_code"
                        wire:model.live.debounce.150ms="form.attendance_code"
                        placeholder="{{ __('admin::leave_types.placeholders.attendance_code') }}"
                    ></x-livewire-input>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('admin::leave_types.hints.attendance_code') }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
                            {{ __('admin::leave_types.hints.attendance_code_preview') }}
                        </span>
                        @if(filled($form['attendance_code'] ?? null))
                            <span class="inline-flex min-w-[3rem] items-center justify-center rounded-lg border border-violet-200 bg-violet-100/90 px-2 py-1 text-[11px] font-semibold uppercase tracking-tight text-violet-700 shadow-sm">
                                {{ $form['attendance_code'] }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-500">{{ __('admin::leave_types.hints.attendance_code_empty') }}</span>
                        @endif
                    </div>
                    @error('form.attendance_code')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.max_days">{{ __('admin::leave_types.fields.max_days') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.max_days"
                        wire:model="form.max_days"></x-livewire-input>
                    @error('form.max_days')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex items-end">
                    <x-checkbox name="form.requires_document"
                        model="form.requires_document">{{ __('admin::leave_types.fields.requires_document') }}</x-checkbox>
                </div>
                <div class="flex items-end">
                    <x-modal-button mode="black">{{ __('admin::leave_types.actions.save') }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('admin::leave_types.fields.id'), __('admin::leave_types.fields.name'), __('admin::leave_types.fields.attendance_code'), __('admin::leave_types.fields.max_days'), __('admin::leave_types.fields.requires_document'), __('admin::leave_types.table.actions')]">
                        @forelse ($leave_types as $type)
                            <tr wire:key="leave-type-row-{{ $type->id }}">
                                <x-table.td>
                                    <span class="text-sm text-zinc-500 font-medium">
                                        {{ $type->id }}
                                    </span>
                                </x-table.td>
                                <x-table.td style="white-space: normal !important;">
                                    <p class="text-sm font-medium">
                                        {{ $type->name }}
                                    </p>
                                </x-table.td>
                                <x-table.td style="white-space: normal !important;">
                                    <p class="text-sm font-medium">
                                        {{ $supportsAttendanceCode ? ($type->attendance_code ?: '-') : '-' }}
                                    </p>
                                </x-table.td>
                                <x-table.td style="white-space: normal !important;">
                                    <p class="text-sm font-medium">
                                        {{ $type->max_days }}
                                    </p>
                                </x-table.td>
                                <x-table.td>
                                    <x-icons.check-icon
                                        color="{{ $type->requires_document_label ? 'text-emerald-500' : 'text-zinc-500' }}"></x-icons.check-icon>
                                </x-table.td>

                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <button type="button" wire:click.prevent="openCrud({{ $type->id }})"
                                            class="appearance-none flex items-center justify-center w-10 h-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 text-xs font-medium uppercase rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700">
                                            <x-icons.edit-icon color="text-zinc-400"
                                                hover="text-zinc-500"></x-icons.edit-icon>
                                        </button>
                                        <button type="button" wire:click.prevent = "deleteModel({{ $type->id }})"
                                            class="flex items-center justify-center w-10 h-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 text-xs font-medium uppercase transition duration-300 rounded-lg text-zinc-500 hover:bg-red-100 hover:text-zinc-700">
                                            <x-icons.delete-icon color="text-rose-500"
                                                hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"></td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
                <div class="mt-2">
                    {{ $leave_types->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
