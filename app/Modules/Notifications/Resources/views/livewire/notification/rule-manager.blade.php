@php
    $displayTemplateKey = static function (?string $key): string {
        return match ($key) {
            'birthday.default' => __('notifications::common.template_keys.birthday.default'),
            'position-change.default' => __('notifications::common.template_keys.position_change.default'),
            'holiday.default' => __('notifications::common.template_keys.holiday.default'),
            null, '' => __('notifications::common.badges.untemplated'),
            default => $key,
        };
    };
    $displayTrigger = static function (?string $trigger): string {
        return $trigger ? __('notifications::common.triggers.'.$trigger) : '—';
    };
    $displayAudienceTarget = static function (?string $target) use ($audienceTargetDefinitions): string {
        return $target ? (data_get($audienceTargetDefinitions, $target.'.label') ?: $target) : '—';
    };
@endphp

<div class="grid gap-5 min-[1700px]:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)]">
    <x-surface-card :title="__('notifications::common.titles.rules')" icon="icons.notification-icon">
        <div class="space-y-4">
            <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200/80 pb-4">
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.rules') }}</p>
                        <p class="mt-1 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.audience_targets_hint_rules') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $categoryLabels[$form['category']] ?? $form['category'] }}</span>
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('notifications::common.channels.'.$form['channel']) }}</span>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.category') }}</label>
                    <select wire:model.live="form.category" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $categoryLabels[$category] ?? $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.channel') }}</label>
                    <select wire:model.defer="form.channel" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="database">{{ __('notifications::common.channels.database') }}</option>
                        <option value="mail">{{ __('notifications::common.channels.mail') }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.trigger') }}</label>
                    <select wire:key="notification-trigger-{{ $form['category'] }}" wire:model.defer="form.trigger" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($triggerOptions as $triggerValue => $triggerLabel)
                            <option value="{{ $triggerValue }}">{{ $triggerLabel }}</option>
                        @endforeach
                    </select>
                    @error('form.trigger') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.template') }}</label>
                    <select wire:model.defer="form.template_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="">{{ __('notifications::common.badges.untemplated') }}</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $displayTemplateKey($template->key) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 xl:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.audience_targets') }}</label>
                    <div class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200/80 pb-4">
                            <div class="max-w-2xl">
                                <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.helpers.audience_targets_summary_title') }}</p>
                                <p class="mt-1 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.audience_targets_summary_hint') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @forelse ($selectedAudienceTargets as $target)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                        {{ $displayAudienceTarget($target) }}
                                    </span>
                                @empty
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                        {{ __('notifications::common.helpers.audience_targets_empty') }}
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($audienceTargetDefinitions as $targetKey => $targetDefinition)
                                @php($isSelected = in_array($targetKey, $selectedAudienceTargets, true))
                                <button
                                    type="button"
                                    wire:click="toggleAudienceTarget('{{ $targetKey }}')"
                                    @class([
                                        'w-full rounded-[1.35rem] border px-4 py-4 text-left transition',
                                        'border-emerald-200 bg-emerald-50/80 shadow-[0_14px_28px_rgba(16,185,129,0.08)]' => $isSelected,
                                        'border-zinc-200 bg-zinc-50/70 hover:border-zinc-300 hover:bg-white' => ! $isSelected,
                                    ])
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-lg font-semibold tracking-tight text-zinc-950">{{ $targetDefinition['label'] }}</p>
                                            <p class="mt-2 max-w-2xl text-sm leading-7 text-zinc-500">{{ $targetDefinition['description'] }}</p>
                                        </div>
                                        <span @class([
                                            'mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-bold',
                                            'border-emerald-300 bg-white text-emerald-700' => $isSelected,
                                            'border-zinc-200 bg-white text-zinc-400' => ! $isSelected,
                                        ])>
                                            {{ $isSelected ? '✓' : '+' }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @error('form.audience_targets') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($showDepartmentPicker || $showSpecificUsersPicker)
                    <div class="xl:col-span-2 grid gap-4 xl:grid-cols-2">
                        @if ($showDepartmentPicker)
                            <div class="space-y-2">
                                <x-ui.searchable-multiselect
                                    :label="__('notifications::common.fields.department_targets')"
                                    model="form.structure_ids"
                                    search-model="structureSearch"
                                    :options="$structureOptions"
                                    :selected-options="$selectedStructureOptions"
                                    search-placeholder="{{ __('notifications::common.helpers.search_structure') }}"
                                    help="{{ __('notifications::common.helpers.audience_targets_structure_hint') }}"
                                />
                                @error('form.structure_ids') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($showSpecificUsersPicker)
                            <div class="space-y-2">
                                <x-ui.searchable-multiselect
                                    :label="__('notifications::common.fields.specific_users')"
                                    model="form.user_ids"
                                    search-model="userSearch"
                                    :options="$userOptions"
                                    :selected-options="$selectedUserOptions"
                                    search-placeholder="{{ __('notifications::common.helpers.search_user') }}"
                                    help="{{ __('notifications::common.helpers.audience_targets_user_hint') }}"
                                />
                                @error('form.user_ids') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="form.approval_required" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                        <span>{{ __('notifications::common.badges.approval_required') }}</span>
                    </label>
                    <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                        <p class="text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.rule_approval_hint') }}</p>
                        @if ($canApproveCampaigns)
                            <button type="button" wire:click="$dispatch('notification-settings-open-tab', { tab: 'approval' })" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700">
                                {{ __('notifications::common.buttons.go_to_approval_queue') }}
                            </button>
                        @endif
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="form.is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                    <span>{{ __('notifications::common.helpers.rule_active') }}</span>
                </label>
            </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                @if ($editingId && $canManageRules)
                    <button type="button" wire:click="resetForm" wire:loading.attr="disabled" wire:target="resetForm" class="rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-600 disabled:cursor-not-allowed disabled:opacity-60">
                        {{ __('notifications::common.buttons.clear') }}
                    </button>
                @endif
                @if ($canManageRules)
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                        {{ $editingId ? __('notifications::common.buttons.update_rule') : __('notifications::common.buttons.save_rule') }}
                    </button>
                @endif
            </div>
        </div>
    </x-surface-card>

    <x-surface-card :title="__('notifications::common.titles.recent_rules')" icon="icons.book-icon">
        <div class="space-y-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('notifications::common.helpers.search_rules') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">

            <div class="space-y-3">
                @forelse ($rules as $rule)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-[0_12px_24px_rgba(15,23,42,0.03)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-zinc-950">{{ $categoryLabels[$rule->category] ?? $rule->category }} / {{ $displayTrigger($rule->trigger) }}</p>
                                <p class="text-xs uppercase tracking-tight font-semibold text-zinc-400">{{ __('notifications::common.channels.'.$rule->channel) }} @if($rule->template) / {{ $displayTemplateKey($rule->template->key) }} @endif</p>
                            </div>
                            <span class="rounded-full border border-zinc-200 px-2.5 py-1 text-[11px] font-semibold {{ $rule->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">
                                {{ $rule->is_active ? __('notifications::common.badges.active') : __('notifications::common.badges.inactive') }}
                            </span>
                        </div>
                        <div class="mt-3 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.fields.audience_targets') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @forelse ((array) data_get($rule->audience_config, 'targets', []) as $target)
                                    <span class="whitespace-nowrap rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700">{{ $displayAudienceTarget($target) }}</span>
                                @empty
                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-500">—</span>
                                @endforelse
                                @if ($rule->approval_required)
                                    <span class="whitespace-nowrap rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ __('notifications::common.helpers.approval_required_short') }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($canManageRules)
                            <div class="mt-4 flex items-center justify-end gap-2">
                                <button type="button" wire:click="edit({{ $rule->id }})" wire:loading.attr="disabled" wire:target="edit" class="rounded-xl border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-600 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.edit') }}</button>
                                <button type="button" wire:click="delete({{ $rule->id }})" wire:loading.attr="disabled" wire:target="delete" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-60">{{ __('notifications::common.buttons.delete') }}</button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-8 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.rule_none') }}
                    </div>
                @endforelse
            </div>
        </div>
    </x-surface-card>
</div>
