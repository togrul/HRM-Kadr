<div class="contents">
    {{-- ===================== şirkət sektoru (standart rejim) ===================== --}}
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::regimes.sector_title') }}</h2>
            <p class="mt-0.5 text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::regimes.sector_hint') }}</p>
        </div>

        <div class="space-y-3 px-4 py-3.5">
            <p class="text-[12.5px] text-ink-soft">
                {{ __('compensation::regimes.current_default', ['regime' => $this->defaultRegimeName ?? '—']) }}
                @unless ($this->sectorIsExplicit)
                    <span class="text-ink-faint">· {{ __('compensation::regimes.from_profile') }}</span>
                @endunless
            </p>

            @if ($canManage)
                <div class="grid gap-3 lg:grid-cols-3">
                    <div class="min-w-0">
                        <x-ui.select-dropdown :label="__('compensation::regimes.sector')" mode="gray" direction="auto" :clearable="false" wire:model.live="sector" :model="$sectors" />
                        @error('sector') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <x-ui.input-shell class="lg:col-span-2" :label="__('compensation::regimes.reason')" :error="$errors->first('sectorReason')">
                        <x-ui.input wire:model="sectorReason" maxlength="500" />
                    </x-ui.input-shell>
                </div>
                <p class="text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::regimes.sector_criteria') }}</p>
                <div class="flex justify-end">
                    <x-pill-button variant="primary" x-on:click="$dispatch('confirm-action', { tone: 'amber', message: {{ \Illuminate\Support\Js::from(__('compensation::regimes.confirm_sector')) }}, confirmText: {{ \Illuminate\Support\Js::from(__('compensation::regimes.save_sector')) }}, run: () => $wire.saveSector() })">{{ __('compensation::regimes.save_sector') }}</x-pill-button>
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== standartdan fərqli rejim ===================== --}}
    <section class="mt-4 overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
            <div class="min-w-0">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::regimes.mismatch_title') }}</h2>
                <p class="mt-0.5 text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::regimes.mismatch_hint') }}</p>
            </div>
            <span class="hrm-num shrink-0 text-[12px] font-medium text-ink-faint">{{ $num(count($this->mismatches)) }}</span>
        </div>

        @if ($this->mismatches === [])
            <div class="px-4 py-8">
                <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::regimes.mismatch_empty')" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[12.5px]">
                    <thead class="bg-[#fafafa] text-[11px] uppercase tracking-wide text-ink-faint">
                        <tr>
                            @if ($canManage)
                                <th class="w-10 px-4 py-2">
                                    <input type="checkbox" wire:click="toggleAll" @checked(count($selected) === count($this->mismatches)) aria-label="{{ __('compensation::regimes.select_all') }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                </th>
                            @endif
                            <th class="px-4 py-2">{{ __('compensation::regimes.employee') }}</th>
                            <th class="px-4 py-2">{{ __('compensation::regimes.current_regime') }}</th>
                            <th class="px-4 py-2">{{ __('compensation::regimes.since') }}</th>
                            <th class="px-4 py-2">{{ __('compensation::regimes.earliest') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline-subtle">
                        @foreach ($this->mismatches as $row)
                            <tr wire:key="compensation-regime-row-{{ $row['tabel_no'] }}">
                                @if ($canManage)
                                    <td class="px-4 py-2"><input type="checkbox" wire:model.live="selected" value="{{ $row['tabel_no'] }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" /></td>
                                @endif
                                <td class="px-4 py-2 text-ink">{{ $row['full_name'] }} <span class="text-ink-faint">· {{ $row['tabel_no'] }}</span></td>
                                <td class="px-4 py-2 text-ink-soft">{{ $row['regime_name'] }}</td>
                                <td class="hrm-num px-4 py-2 text-ink-faint">{{ $row['effective_from'] ? \Illuminate\Support\Carbon::parse($row['effective_from'])->format('d.m.Y') : '—' }}</td>
                                <td class="hrm-num px-4 py-2 text-ink-faint">{{ \Illuminate\Support\Carbon::parse($row['earliest_date'])->format('d.m.Y') }}</td>
                            </tr>
                            @isset($skipped[$row['tabel_no']])
                                <tr wire:key="compensation-regime-skip-{{ $row['tabel_no'] }}">
                                    <td colspan="5" class="bg-amber-50 px-4 py-1.5 text-[11.5px] text-amber-800">{{ $skipped[$row['tabel_no']] }}</td>
                                </tr>
                            @endisset
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($canManage)
                <div class="space-y-3 border-t border-hairline-subtle px-4 py-3.5">
                    <div class="grid gap-3 lg:grid-cols-3">
                        <x-ui.input-shell :label="__('compensation::regimes.effective_from')" :error="$errors->first('alignDate')">
                            <x-ui.input type="date" wire:model="alignDate" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="lg:col-span-2" :label="__('compensation::regimes.reason')" :error="$errors->first('alignReason')">
                            <x-ui.input wire:model="alignReason" maxlength="500" />
                        </x-ui.input-shell>
                    </div>
                    @error('selected') <x-validation>{{ $message }}</x-validation> @enderror
                    <p class="text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::regimes.align_hint', ['regime' => $this->defaultRegimeName ?? '—']) }}</p>
                    <div class="flex justify-end">
                        <x-pill-button variant="primary" :disabled="count($selected) === 0" x-on:click="$dispatch('confirm-action', { tone: 'amber', message: {{ \Illuminate\Support\Js::from(__('compensation::regimes.confirm_align', ['regime' => $this->defaultRegimeName ?? '—'])) }}, confirmText: {{ \Illuminate\Support\Js::from(__('compensation::regimes.align')) }}, run: () => $wire.realign() })">
                            {{ __('compensation::regimes.align_selected', ['count' => count($selected)]) }}
                        </x-pill-button>
                    </div>
                </div>
            @endif
        @endif
    </section>
</div>
