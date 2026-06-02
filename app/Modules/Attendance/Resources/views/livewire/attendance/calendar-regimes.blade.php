@php
    use App\Support\Translations\ModuleTranslation;
@endphp

<div class="space-y-4">
    <x-surface-card :title="__('attendance::calendar_regimes.title')" icon="icons.calendar-icon">
        <p class="text-sm text-zinc-500">
            {{ __('attendance::calendar_regimes.description') }}
        </p>
    </x-surface-card>

    <x-surface-card :title="$editingId ? __('attendance::calendar_regimes.cards.edit') : __('attendance::calendar_regimes.cards.create')">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-label for="form.date">{{ __('attendance::calendar_regimes.fields.date') }}</x-label>
                <x-pikaday-input mode="gray" name="form.date" format="Y-MM-DD" wire:model.live="form.date">
                    <x-slot name="script">
                        $el.onchange = function () {
                            @this.set('form.date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('form.date') <x-validation>{{ $message }}</x-validation> @enderror
            </div>

            <div>
                <x-label for="attendance-calendar-day-type">{{ __('attendance::calendar_regimes.fields.day_type') }}</x-label>
                <select id="attendance-calendar-day-type" wire:model.live="form.day_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                    <option value="workday">{{ __('attendance::calendar_regimes.options.workday') }}</option>
                    <option value="weekend">{{ __('attendance::calendar_regimes.options.weekend') }}</option>
                    <option value="holiday">{{ __('attendance::calendar_regimes.options.holiday') }}</option>
                </select>
                @error('form.day_type') <x-validation>{{ $message }}</x-validation> @enderror
            </div>

            <div>
                <x-label for="attendance-calendar-scope-type">{{ __('attendance::calendar_regimes.fields.scope_type') }}</x-label>
                <select id="attendance-calendar-scope-type" wire:model.live="form.scope_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                    <option value="global">{{ __('attendance::calendar_regimes.options.global') }}</option>
                    <option value="structure">{{ __('attendance::calendar_regimes.options.structure') }}</option>
                </select>
                @error('form.scope_type') <x-validation>{{ $message }}</x-validation> @enderror
            </div>

            @if(($form['scope_type'] ?? 'global') === 'structure')
                <div>
                    <x-label for="attendance-calendar-structure">{{ __('attendance::calendar_regimes.fields.structure') }}</x-label>
                    <select id="attendance-calendar-structure" wire:model.live="form.scope_id" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                        <option value="">{{ __('attendance::calendar_regimes.options.select_structure') }}</option>
                        @foreach($structures as $structure)
                            <option value="{{ $structure->id }}">{{ $structure->name }}</option>
                        @endforeach
                    </select>
                    @error('form.scope_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
            @endif

            <div>
                <x-label for="attendance-calendar-name">{{ __('attendance::calendar_regimes.fields.name') }}</x-label>
                <x-livewire-input id="attendance-calendar-name" mode="gray" name="form.name" wire:model.live="form.name" />
                @error('form.name') <x-validation>{{ $message }}</x-validation> @enderror
            </div>

            <div class="flex items-end">
                <x-checkbox name="form.is_paid" model="form.is_paid">{{ __('attendance::calendar_regimes.fields.is_paid') }}</x-checkbox>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <x-button mode="primary" wire:click="save">{{ __('attendance::calendar_regimes.actions.save') }}</x-button>
            @if($editingId)
                <x-button mode="slate" wire:click="cancel">{{ __('attendance::calendar_regimes.actions.cancel') }}</x-button>
            @endif
        </div>
    </x-surface-card>

    <x-surface-card :title="__('attendance::calendar_regimes.cards.list')">
        <x-table.tbl :headers="[
            __('attendance::calendar_regimes.fields.date'),
            __('attendance::calendar_regimes.fields.day_type'),
            __('attendance::calendar_regimes.fields.scope'),
            __('attendance::calendar_regimes.fields.name'),
            __('attendance::calendar_regimes.fields.is_paid'),
            'action'
        ]">
            @forelse($calendars as $calendar)
                <tr wire:key="attendance-calendar-{{ $calendar->id }}">
                    <x-table.td>{{ $calendar->date?->format('Y-m-d') }}</x-table.td>
                    <x-table.td>{{ __('attendance::calendar_regimes.options.'.$calendar->day_type) }}</x-table.td>
                    <x-table.td>
                        @if($calendar->scope_type === 'structure')
                            {{ __('attendance::calendar_regimes.options.structure') }}: {{ $structureNames[$calendar->scope_id] ?? ('#'.$calendar->scope_id) }}
                        @else
                            {{ __('attendance::calendar_regimes.options.global') }}
                        @endif
                    </x-table.td>
                    <x-table.td>{{ $calendar->name ? ModuleTranslation::resolveStoredText((string) $calendar->name) : '-' }}</x-table.td>
                    <x-table.td>{{ $calendar->is_paid ? __('attendance::calendar_regimes.options.yes') : __('attendance::calendar_regimes.options.no') }}</x-table.td>
                    <x-table.td :isButton="true" width="100">
                        <div class="flex items-center space-x-2">
                            <a
                                href="{{ route('attendance', ['tab' => 'history', 'history_type' => 'calendar', 'history_subject_id' => $calendar->id]) }}"
                                class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-700"
                                title="{{ __('attendance::history.actions.open_filtered_history') }}"
                            >
                                <x-icons.info-circle-icon color="text-sky-500" hover="text-sky-600"></x-icons.info-circle-icon>
                            </a>
                            <button wire:click.prevent="edit({{ $calendar->id }})" class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                            </button>
                            <button wire:click.prevent="confirmRemove({{ $calendar->id }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700">
                                <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
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

        <div class="mt-2">
            {{ $calendars->links() }}
        </div>
    </x-surface-card>

    <x-ui.delete-confirmation-modal />
</div>
