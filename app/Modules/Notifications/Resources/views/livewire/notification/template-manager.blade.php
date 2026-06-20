@php
    $displayTemplateKey = static function (string $key): string {
        return match ($key) {
            'birthday.default' => __('notifications::common.template_keys.birthday.default'),
            'position-change.default' => __('notifications::common.template_keys.position_change.default'),
            'holiday.default' => __('notifications::common.template_keys.holiday.default'),
            default => $key,
        };
    };
@endphp

<div class="grid gap-5 min-[1700px]:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.95fr)]">
    <x-surface-card :title="__('notifications::common.titles.templates')" icon="icons.layout-icon">
        <div class="space-y-4">
            <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200/80 pb-4">
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.templates') }}</p>
                        <p class="mt-1 text-sm leading-6 text-zinc-500">{{ __('notifications::common.helpers.template_preview_hint') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $categoryLabels[$form['category']] ?? $form['category'] }}</span>
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('notifications::common.channels.'.$form['channel']) }}</span>
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-4 xl:grid-cols-2">
                <x-ui.input-shell class="space-y-2 xl:col-span-2" :label="__('notifications::common.fields.key')" :error="$errors->first('form.key')">
                    <input type="text" wire:model.defer="form.key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.category')">
                    <select wire:model.live="form.category" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $categoryLabels[$category] ?? $category }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.channel')">
                    <select wire:model.live="form.channel" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="database">{{ __('notifications::common.channels.database') }}</option>
                        <option value="mail">{{ __('notifications::common.channels.mail') }}</option>
                    </select>
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.format')">
                    <select wire:model.live="form.format" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                        <option value="text">{{ __('notifications::common.formats.text') }}</option>
                        <option value="html">{{ __('notifications::common.formats.html') }}</option>
                    </select>
                </x-ui.input-shell>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.defer="form.is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                    <span>{{ __('notifications::common.helpers.template_active') }}</span>
                </label>

                <x-ui.input-shell class="space-y-2 xl:col-span-2" :label="__('notifications::common.fields.subject')">
                    <input type="text" wire:model.live.debounce.300ms="form.subject_template" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">
                </x-ui.input-shell>

                <x-ui.input-shell class="space-y-2 xl:col-span-2" :label="__('notifications::common.fields.body')" :error="$errors->first('form.body_template')">
                    <textarea wire:model.live.debounce.300ms="form.body_template" rows="7" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm leading-6 text-zinc-800"></textarea>
                </x-ui.input-shell>
                    </div>

                    <div class="space-y-4">
                        <x-notification.preview-card
                            :title="__('notifications::common.titles.live_preview')"
                            :subject-label="__('notifications::common.fields.subject')"
                            :subject="$previewSubject"
                            :body-label="__('notifications::common.fields.body')"
                            :body="$previewBody"
                            :fallback="__('notifications::common.helpers.template_live_preview_empty')"
                            body-class="whitespace-pre-wrap"
                        />
                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950">{{ __('notifications::common.titles.variables_helper') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('notifications::common.helpers.variables_helper_hint') }}</p>
                                </div>
                            <x-notification.chip mode="muted" size="sm">
                                {{ $categoryLabels[$form['category']] ?? $form['category'] }}
                            </x-notification.chip>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($availableVariables as $variable)
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                        <p class="font-mono text-[13px] tracking-tight font-semibold text-zinc-900">{{ $variable['token'] }}</p>
                                        <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $variable['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_12rem] xl:items-end">
                        <x-ui.input-shell class="space-y-2" :label="__('notifications::common.fields.test_email')" :error="$errors->first('testEmail')">
                            <input type="email" wire:model.defer="testEmail" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800" placeholder="{{ __('notifications::common.helpers.demo_email') }}">
                        </x-ui.input-shell>
                        <x-ui.async-button type="button" variant="secondary" fullWidth="true" size="lg" wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest">
                            {{ __('notifications::common.buttons.send_test') }}
                        </x-ui.async-button>
                    </div>
                    @if ($testStatus)
                        <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ $testStatus }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                @if ($editingId && $canManageTemplates)
                    <x-ui.async-button type="button" variant="secondary" wire:click="resetForm" wire:loading.attr="disabled" wire:target="resetForm">{{ __('notifications::common.buttons.clear') }}</x-ui.async-button>
                @endif
                @if ($canManageTemplates)
                    <x-ui.async-button type="button" variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editingId ? __('notifications::common.buttons.update_template') : __('notifications::common.buttons.save_template') }}</x-ui.async-button>
                @endif
            </div>
        </div>
    </x-surface-card>

    <x-surface-card :title="__('notifications::common.titles.recent_templates')" icon="icons.book-icon">
        <div class="space-y-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('notifications::common.helpers.search_templates') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800">

            <div class="space-y-3">
                @forelse ($templates as $template)
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-[0_12px_24px_rgba(15,23,42,0.03)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-zinc-950">{{ $displayTemplateKey($template->key) }}</p>
                                <p class="text-xs uppercase font-semibold tracking-tight text-zinc-400">{{ $categoryLabels[$template->category] ?? $template->category }} / {{ __('notifications::common.channels.'.$template->channel) }} / {{ __('notifications::common.formats.'.$template->format) }}</p>
                            </div>
                            <x-notification.chip :mode="$template->is_active ? 'active' : 'inactive'" size="sm">
                                {{ $template->is_active ? __('notifications::common.badges.active') : __('notifications::common.badges.inactive') }}
                            </x-notification.chip>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm leading-6 text-zinc-600">{{ $template->subject_template ?: $template->body_template }}</p>
                        @if ($canManageTemplates)
                            <div class="mt-4 flex items-center justify-end gap-2">
                                <x-ui.async-button type="button" variant="secondary" size="sm" wire:click="edit({{ $template->id }})" wire:loading.attr="disabled" wire:target="edit">{{ __('notifications::common.buttons.edit') }}</x-ui.async-button>
                                <x-ui.async-button type="button" variant="danger" size="sm" wire:click="delete({{ $template->id }})" wire:loading.attr="disabled" wire:target="delete">{{ __('notifications::common.buttons.delete') }}</x-ui.async-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/70 px-4 py-8 text-sm text-zinc-500">
                        {{ __('notifications::common.helpers.template_none') }}
                    </div>
                @endforelse
            </div>
        </div>
    </x-surface-card>
</div>
