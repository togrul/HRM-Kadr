@php
    $displayAudienceTarget = static function (?string $target) use ($audienceTargetDefinitions): string {
        return $target ? (data_get($audienceTargetDefinitions, $target.'.label') ?: $target) : '—';
    };
@endphp

<x-surface-card :title="__('notifications::common.titles.announcement_composer')" icon="icons.notification-icon">
    <div class="grid gap-5 min-[1700px]:grid-cols-[minmax(0,1.18fr)_minmax(24rem,0.82fr)]">
        <div class="space-y-4 rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.category') }}</label>
                    <select wire:model.live="form.category" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="announcement">{{ __('notifications::common.categories.announcement') }}</option>
                        <option value="holiday">{{ __('notifications::common.categories.holiday') }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.schedule_mode') }}</label>
                    <select wire:model.live="form.schedule_mode" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($scheduleModes as $mode => $label)
                            <option value="{{ $mode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-zinc-500">{{ __('notifications::common.helpers.schedule_hint_event_driven') }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.channel') }}</label>
                    <select wire:model.defer="form.channel" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="database">{{ __('notifications::common.channels.database') }}</option>
                        <option value="mail">{{ __('notifications::common.channels.mail') }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.format') }}</label>
                    <select wire:model.defer="form.format" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="text">{{ __('notifications::common.formats.text') }}</option>
                        <option value="html">{{ __('notifications::common.formats.html') }}</option>
                    </select>
                </div>
            </div>

            <div class="rounded-[1.4rem] border border-zinc-200 bg-white px-4 py-4 shadow-[0_10px_26px_rgba(15,23,42,0.04)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.schedule_experience') }}</p>
                        <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.schedule_preview_label') }}: <span class="font-semibold text-zinc-700">{{ $resolvedSchedulePreview }}</span></p>
                        @if ($matchedRuleLabel)
                            <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.matched_rule_label') }}: <span class="font-semibold text-zinc-700">{{ $matchedRuleLabel }}</span></p>
                        @endif
                    </div>
                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                        {{ __('notifications::common.schedule_modes.'.$form['schedule_mode']) }}
                    </span>
                </div>

                @if ($form['schedule_mode'] === 'custom')
                    <div class="mt-4 space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.scheduled_at') }}</label>
                        <input type="datetime-local" wire:model.defer="form.scheduled_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3 text-sm text-zinc-800">
                        @error('form.scheduled_at') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.title') }}</label>
                    <input type="text" wire:model.defer="form.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                    @error('form.title') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($form['category'] === 'holiday')
                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.holiday_name') }}</label>
                        <input type="text" wire:model.defer="form.holiday_name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @error('form.holiday_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.holiday_date') }}</label>
                        <input type="date" wire:model.defer="form.holiday_date" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @error('form.holiday_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.duration') }}</label>
                        <input type="text" wire:model.defer="form.duration" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.scope') }}</label>
                        <input type="text" wire:model.defer="form.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @error('form.scope') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.holiday_rules') }}</label>
                        <textarea wire:model.defer="form.holiday_rules" rows="4" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm leading-6 text-zinc-800"></textarea>
                    </div>
                @endif

                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.body') }}</label>
                    <textarea wire:model.defer="form.body" rows="{{ $form['category'] === 'holiday' ? 4 : 7 }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm leading-6 text-zinc-800"></textarea>
                    @error('form.body') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-4 rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="grid gap-4">
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.fields.audience_targets') }}</label>
                    <div class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200/80 pb-4">
                            <div class="max-w-2xl">
                                <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.helpers.audience_targets_summary_title_announcements') }}</p>
                                <p class="mt-1 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.audience_targets_summary_hint_announcements') }}</p>
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

                @if ($needsStructurePicker)
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

                @if ($needsUserPicker)
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

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="form.approval_required" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                    <span>{{ __('notifications::common.announcements.approval_required') }}</span>
                </label>
                @if ($matchedRuleLabel)
                    <p class="text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.manual_campaign_rule_hint') }}</p>
                @endif

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="form.send_now" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                        <span>{{ __('notifications::common.announcements.send_now') }}</span>
                </label>
            </div>

                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-[0_14px_30px_rgba(15,23,42,0.04)]">
                    <p class="text-sm font-semibold text-zinc-900">{{ __('notifications::common.announcements.preview') }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.manual_announcement_hint') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 font-medium text-zinc-600">{{ __('notifications::common.fields.channel') }}: {{ __('notifications::common.channels.'.$form['channel']) }}</span>
                        <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 font-medium text-zinc-600">{{ __('notifications::common.fields.schedule_mode') }}: {{ __('notifications::common.schedule_modes.'.$form['schedule_mode']) }}</span>
                    </div>
                    <div class="mt-4 rounded-[1.35rem] border border-zinc-200 bg-zinc-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('notifications::common.helpers.audience_summary_preview') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($audienceSelectionSummary['highlights'] as $highlight)
                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ $highlight }}</span>
                            @empty
                                <span class="text-sm text-zinc-400">{{ __('notifications::common.helpers.audience_targets_empty') }}</span>
                            @endforelse
                        </div>
                        @if ($audienceSelectionSummary['facts'] !== [])
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($audienceSelectionSummary['facts'] as $fact)
                                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600">{{ $fact }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <p class="mt-4 text-base font-semibold tracking-tight text-zinc-900">{{ $form['title'] ?: __('notifications::common.announcements.preview_title_fallback') }}</p>
                    @if ($form['category'] === 'holiday')
                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                            <span>{{ $form['holiday_name'] ?: '—' }}</span>
                        <span>{{ $form['holiday_date'] ?: '—' }}</span>
                        <span>{{ $form['duration'] ?: '—' }}</span>
                        <span>{{ $form['scope'] ?: '—' }}</span>
                    </div>
                @endif
                <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $form['body'] ?: __('notifications::common.helpers.announcement_preview_empty') }}</p>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="resetForm" wire:loading.attr="disabled" wire:target="resetForm" class="rounded-2xl border border-zinc-200 px-5 py-2.5 text-sm font-semibold text-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">
                    {{ __('notifications::common.buttons.clear') }}
                </button>
                @if ($canManageCampaigns)
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="rounded-2xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                    {{ __('notifications::common.buttons.save_campaign') }}
                </button>
                @endif
            </div>
        </div>
    </div>
</x-surface-card>
