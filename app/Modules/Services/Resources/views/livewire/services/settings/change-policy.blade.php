<div class="flex flex-col gap-4">
    @php
        $chip = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium';
        $modeTone = [
            'free' => 'bg-[#f4f4f5] text-ink-muted',
            'journal' => 'bg-sky-50 text-sky-700',
            'order' => 'bg-amber-50 text-amber-700',
        ];
    @endphp

    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.change_policy.title') }}</h2>
            <p class="mt-0.5 max-w-3xl text-[11.5px] leading-5 text-ink-faint">{{ __('services::settings.change_policy.description') }}</p>
        </div>

        <div class="hidden grid-cols-12 gap-4 border-b border-hairline-subtle bg-[#fafafa] px-4 py-2 md:grid">
            <p class="hrm-eyebrow col-span-6">{{ __('services::settings.change_policy.columns.group') }}</p>
            <p class="hrm-eyebrow col-span-3">{{ __('services::settings.change_policy.columns.mode') }}</p>
            <p class="hrm-eyebrow col-span-3">{{ __('services::settings.change_policy.columns.source') }}</p>
        </div>

        <div class="divide-y divide-hairline-subtle">
            @foreach ($rows as $row)
                <div class="grid grid-cols-1 items-center gap-3 px-4 py-3 md:grid-cols-12 md:gap-4" wire:key="change-policy-{{ $row['group'] }}">
                    <div class="min-w-0 md:col-span-6">
                        <p class="text-[13px] font-medium text-ink">{{ $row['label'] }}</p>
                        <p class="mt-0.5 text-[11.5px] leading-4 text-ink-faint">{{ $row['description'] }}</p>
                        @if ($row['owner'] === 'compensation')
                            <span class="{{ $chip }} mt-1 bg-[#f4f4f5] text-ink-muted">{{ __('services::settings.change_policy.owner_compensation') }}</span>
                        @endif
                    </div>

                    <div class="min-w-0 md:col-span-3">
                        <x-ui.select wire:model.live="modes.{{ $row['group'] }}" aria-label="{{ __('services::settings.change_policy.columns.mode') }}: {{ $row['label'] }}">
                            @foreach ($modeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="mt-1 text-[11px] leading-4 text-ink-faint">{{ __('personnel::change_policy.mode_hints.'.$row['mode']) }}</p>
                        @error('modes.'.$row['group'])
                            <x-validation>{{ $message }}</x-validation>
                        @enderror
                    </div>

                    <div class="flex min-w-0 items-center justify-between gap-2 md:col-span-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($row['customized'])
                                    <span class="{{ $chip }} bg-emerald-50 text-emerald-700">{{ __('services::settings.change_policy.sources.customized') }}</span>
                                @else
                                    <span class="{{ $chip }} bg-[#f4f4f5] text-ink-muted">{{ __('services::settings.change_policy.sources.default') }}</span>
                                @endif
                                <span class="{{ $chip }} {{ $modeTone[$row['mode']] ?? $modeTone['free'] }}">{{ $modeOptions[$row['mode']] ?? $row['mode'] }}</span>
                            </div>
                            @if ($row['customized'])
                                <p class="mt-1 truncate text-[11px] text-ink-faint">{{ __('services::settings.change_policy.default_mode', ['mode' => $modeOptions[$row['default_mode']] ?? $row['default_mode']]) }}</p>
                                @if ($row['updated_at'])
                                    <p class="hrm-num truncate text-[11px] text-ink-faint">{{ __('services::settings.change_policy.changed_by', ['user' => $row['updated_by'] ?? '—', 'date' => $row['updated_at']]) }}</p>
                                @endif
                            @endif
                        </div>

                        @if ($row['customized'])
                            <x-pill-button
                                variant="ghost"
                                data-title="{{ __('services::settings.change_policy.confirm.reset_title') }}"
                                data-message="{{ __('services::settings.change_policy.confirm.reset_message', ['group' => $row['label'], 'mode' => $modeOptions[$row['default_mode']] ?? $row['default_mode']]) }}"
                                data-confirm="{{ __('services::settings.change_policy.actions.reset') }}"
                                x-on:click="$dispatch('confirm-action', { title: $el.dataset.title, message: $el.dataset.message, confirmText: $el.dataset.confirm, tone: 'amber', run: () => $wire.resetGroup('{{ $row['group'] }}') })"
                            >{{ __('services::settings.change_policy.actions.reset') }}</x-pill-button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
