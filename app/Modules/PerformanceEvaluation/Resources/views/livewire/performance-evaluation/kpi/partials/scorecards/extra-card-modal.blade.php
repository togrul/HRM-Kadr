@can('manage-performance-evaluation')
    <x-side-modal size="large">
        @if ($showSideMenu === 'extra-card')
            @php
                $chosenPerson = collect($this->extraPersonnelOptions)->firstWhere('id', (int) $extraPersonnelId);
                $chosenPosition = collect($this->extraPositionOptions)->firstWhere('id', (int) $extraPositionId);
            @endphp
            <div class="flex h-full flex-col">
                <p class="hrm-eyebrow">{{ __($t.'.extra.open') }}</p>
                <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ __($t.'.extra.title') }}</h2>
                <p class="mt-3 rounded-xl bg-[#fafafa] px-3 py-2.5 text-[12.5px] leading-5 text-ink-muted">{{ __($t.'.extra.hint') }}</p>

                <div class="mt-6 flex flex-col gap-5">
                    <div>
                        <x-ui.select-dropdown :label="__($t.'.extra.person')" :placeholder="__($t.'.extra.search')" mode="gray" class="w-full" instance="kpi-extra-person" direction="auto"
                            wire:model.live="extraPersonnelId" :model="$this->extraPersonnelOptions" search-model="extraSearch"></x-ui.select-dropdown>
                        @error('extraPersonnelId') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-ui.select-dropdown :label="__($t.'.extra.position')" placeholder="—" mode="gray" class="w-full" instance="kpi-extra-position" direction="auto"
                            wire:model.live="extraPositionId" :model="$this->extraPositionOptions"></x-ui.select-dropdown>
                        <p class="mt-1.5 text-[11.5px] text-ink-faint">{{ __($t.'.extra.position_hint') }}</p>
                        @error('extraPositionId') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-label value="{{ __($t.'.extra.fte') }}" />
                        <div class="mt-1.5 grid grid-cols-4 gap-1 rounded-xl bg-[#f4f4f5] p-1">
                            @foreach ([0.25, 0.5, 0.75, 1.0] as $share)
                                <button type="button" wire:click="$set('extraFte', {{ $share }})"
                                    class="hrm-num h-10 rounded-lg text-[14px] font-semibold transition {{ abs((float) $extraFte - $share) < 0.001 ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink' }}">
                                    {{ rtrim(rtrim(number_format($share, 2, '.', ''), '0'), '.') }}
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-[11.5px] text-ink-faint">{{ __($t.'.extra.fte_hint') }}</p>
                        @error('extraFte') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                </div>

                @if ($chosenPerson && $chosenPosition)
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-hairline bg-white p-4">
                        <x-avatar :name="$chosenPerson['label']" size="md" />
                        <p class="text-[12.5px] leading-5 text-ink-soft">{{ __($t.'.extra.summary', ['person' => \Illuminate\Support\Str::before($chosenPerson['label'], ' ·'), 'position' => $chosenPosition['label'], 'fte' => rtrim(rtrim(number_format((float) $extraFte, 2, '.', ''), '0'), '.')]) }}</p>
                    </div>
                @endif

                <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                    <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($t.'.actions.cancel') }}</button>
                    <button type="button" wire:click="openExtraCard" wire:loading.attr="disabled" wire:target="openExtraCard" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover disabled:opacity-50">{{ __($t.'.extra.submit') }}</button>
                </div>
            </div>
        @endif
    </x-side-modal>
@endcan
