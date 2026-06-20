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
                        <x-notification.chip mode="neutral" size="sm" uppercase>{{ $categoryLabels[$form['category']] ?? $form['category'] }}</x-notification.chip>
                        <x-notification.chip mode="neutral" size="sm" uppercase>{{ __('notifications::common.channels.'.$form['channel']) }}</x-notification.chip>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.category')">
                    <select wire:model.live="form.category" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $categoryLabels[$category] ?? $category }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.channel')">
                    <select wire:model.defer="form.channel" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="database">{{ __('notifications::common.channels.database') }}</option>
                        <option value="mail">{{ __('notifications::common.channels.mail') }}</option>
                    </select>
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.trigger')" :error="$errors->first('form.trigger')">
                    <select wire:key="notification-trigger-{{ $form['category'] }}" wire:model.defer="form.trigger" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($triggerOptions as $triggerValue => $triggerLabel)
                            <option value="{{ $triggerValue }}">{{ $triggerLabel }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.template')">
                    <select wire:model.defer="form.template_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="">{{ __('notifications::common.badges.untemplated') }}</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $displayTemplateKey($template->key) }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>

                <div class="space-y-2 xl:col-span-2">
                    <x-notification.audience-selector
                        :field-label="__('notifications::common.fields.audience_targets')"
                        :summary-title="__('notifications::common.helpers.audience_targets_summary_title')"
                        :summary-hint="__('notifications::common.helpers.audience_targets_summary_hint')"
                        :definitions="$audienceTargetDefinitions"
                        :selected="$selectedAudienceTargets"
                        :empty-label="__('notifications::common.helpers.audience_targets_empty')"
                        :selected-label="__('notifications::common.labels.selected')"
                    />
                    @error('form.audience_targets') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                @if ($showDepartmentPicker || $showSpecificUsersPicker)
                    <div class="xl:col-span-2 grid gap-4 min-[1400px]:grid-cols-2">
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
                                @error('form.structure_ids') <x-validation>{{ $message }}</x-validation> @enderror
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
                                @error('form.user_ids') <x-validation>{{ $message }}</x-validation> @enderror
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
                            <x-ui.async-button type="button" variant="secondary" size="sm" wire:click="$dispatch('notification-settings-open-tab', { tab: 'approval' })">{{ __('notifications::common.buttons.go_to_approval_queue') }}</x-ui.async-button>
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
                    <x-ui.async-button type="button" variant="secondary" wire:click="resetForm" wire:loading.attr="disabled" wire:target="resetForm">{{ __('notifications::common.buttons.clear') }}</x-ui.async-button>
                @endif
                @if ($canManageRules)
                    <x-ui.async-button type="button" variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editingId ? __('notifications::common.buttons.update_rule') : __('notifications::common.buttons.save_rule') }}</x-ui.async-button>
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
                            <x-notification.chip :mode="$rule->is_active ? 'active' : 'inactive'" size="sm">
                                {{ $rule->is_active ? __('notifications::common.badges.active') : __('notifications::common.badges.inactive') }}
                            </x-notification.chip>
                        </div>
                        <div class="mt-3 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('notifications::common.fields.audience_targets') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @forelse ((array) data_get($rule->audience_config, 'targets', []) as $target)
                                    <x-notification.chip mode="muted">{{ $displayAudienceTarget($target) }}</x-notification.chip>
                                @empty
                                    <x-notification.chip mode="muted">—</x-notification.chip>
                                @endforelse
                                @if ($rule->approval_required)
                                    <x-notification.chip mode="amber" uppercase>{{ __('notifications::common.helpers.approval_required_short') }}</x-notification.chip>
                                @endif
                            </div>
                        </div>
                        @if ($canManageRules)
                            <div class="mt-4 flex items-center justify-end gap-2">
                                <x-ui.async-button type="button" variant="secondary" size="sm" wire:click="edit({{ $rule->id }})" wire:loading.attr="disabled" wire:target="edit">{{ __('notifications::common.buttons.edit') }}</x-ui.async-button>
                                <x-ui.async-button type="button" variant="danger" size="sm" wire:click="delete({{ $rule->id }})" wire:loading.attr="disabled" wire:target="delete">{{ __('notifications::common.buttons.delete') }}</x-ui.async-button>
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
