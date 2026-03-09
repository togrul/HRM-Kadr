<div class="space-y-4">
    <x-surface-card :title="__('attendance::settings.title')" icon="icons.line-settings-icon">
        <p class="text-sm text-zinc-500">
            {{ __('attendance::settings.description') }}
        </p>
    </x-surface-card>

    <x-surface-card :title="__('attendance::settings.global_policy')">
        @if($currentDefaultShift)
            <div class="mb-3 rounded-xl border border-blue-100 bg-blue-50 p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-blue-900">{{ __('attendance::settings.default_shift.current_title') }}</p>
                        <p class="text-xs text-blue-700">{{ __('attendance::settings.default_shift.current_description') }}</p>
                    </div>
                    <div class="flex flex-col items-start gap-1">
                        <x-small-badge mode="sky">{{ $currentDefaultShift->name }}</x-small-badge>
                        <span class="text-xs text-blue-700">
                            {{ $currentDefaultShift->start_time }} - {{ $currentDefaultShift->end_time }}
                        </span>
                    </div>
                </div>
            </div>
        @else
            <div class="mb-3 rounded-xl border border-amber-100 bg-amber-50 p-3 text-sm text-amber-700">
                <div class="flex items-center gap-2">
                    <x-small-badge mode="red">{{ __('attendance::settings.default_shift.none_badge') }}</x-small-badge>
                    <span>{{ __('attendance::settings.default_shift.none_description') }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-label for="attendance-settings-timezone">{{ __('attendance::settings.fields.timezone') }}</x-label>
                <select
                    id="attendance-settings-timezone"
                    wire:model.defer="form.timezone"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    @disabled(! $canManage)
                >
                    @foreach(['Asia/Baku', 'UTC', 'Europe/Istanbul', 'Europe/Moscow'] as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
                @error('form.timezone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-label for="attendance-settings-default-shift">{{ __('attendance::settings.fields.default_shift') }}</x-label>
                <select
                    id="attendance-settings-default-shift"
                    wire:model.defer="form.default_shift_id"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    @disabled(! $canManage)
                >
                    <option value="">{{ __('attendance::settings.default_shift.option_none') }}</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>
                @error('form.default_shift_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-label for="attendance-settings-late-grace">{{ __('attendance::settings.fields.late_grace') }}</x-label>
                <x-livewire-input
                    id="attendance-settings-late-grace"
                    mode="gray"
                    type="number"
                    min="0"
                    max="300"
                    wire:model.defer="form.late_grace_minutes"
                    @disabled(! $canManage)
                />
                @error('form.late_grace_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-label for="attendance-settings-early-grace">{{ __('attendance::settings.fields.early_grace') }}</x-label>
                <x-livewire-input
                    id="attendance-settings-early-grace"
                    mode="gray"
                    type="number"
                    min="0"
                    max="300"
                    wire:model.defer="form.early_leave_grace_minutes"
                    @disabled(! $canManage)
                />
                @error('form.early_leave_grace_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-label for="attendance-settings-rounding-policy">{{ __('attendance::settings.fields.rounding_policy') }}</x-label>
                <select
                    id="attendance-settings-rounding-policy"
                    wire:model.defer="form.rounding_policy"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    @disabled(! $canManage)
                >
                    <option value="none">{{ __('attendance::settings.options.none') }}</option>
                    <option value="floor">{{ __('attendance::settings.options.floor') }}</option>
                    <option value="ceil">{{ __('attendance::settings.options.ceil') }}</option>
                    <option value="nearest">{{ __('attendance::settings.options.nearest') }}</option>
                </select>
                @error('form.rounding_policy') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-label for="attendance-settings-rounding-step">{{ __('attendance::settings.fields.rounding_step') }}</x-label>
                <x-livewire-input
                    id="attendance-settings-rounding-step"
                    mode="gray"
                    type="number"
                    min="1"
                    max="60"
                    wire:model.defer="form.rounding_step_minutes"
                    @disabled(! $canManage)
                />
                @error('form.rounding_step_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2 lg:col-span-3">
                <x-label for="attendance-settings-overtime-policy">{{ __('attendance::settings.fields.overtime_policy') }}</x-label>
                <select
                    id="attendance-settings-overtime-policy"
                    wire:model.defer="form.overtime_policy"
                    class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    @disabled(! $canManage)
                >
                    <option value="by_approval">{{ __('attendance::settings.options.by_approval') }}</option>
                    <option value="none">{{ __('attendance::settings.options.none') }}</option>
                    <option value="all_worked">{{ __('attendance::settings.options.all_worked') }}</option>
                    <option value="after_shift">{{ __('attendance::settings.options.after_shift') }}</option>
                </select>
                @error('form.overtime_policy') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        @if($canManage)
            <div class="mt-4">
                <x-button mode="primary" wire:click="save">{{ __('attendance::settings.actions.save') }}</x-button>
            </div>
        @endif
    </x-surface-card>
</div>
