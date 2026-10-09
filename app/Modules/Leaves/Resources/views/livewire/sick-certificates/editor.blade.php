<div>
    @if ($open)
        @php
            $certificate = $this->certificate;
            $warnings = $this->warnings;
            $employeeLocked = $fixedTabelNo !== null || $mode !== 'create';
        @endphp

        <x-ui.side-panel
            title-id="sick-certificate-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('leaves::sick_certificates.actions.dismiss')"
            width="2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('leaves::sick_certificates.panel.kicker') }}</p>
                    <h2 id="sick-certificate-panel-title" class="mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ __('leaves::sick_certificates.panel.'.$mode) }}</h2>
                </div>

                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('leaves::sick_certificates.actions.dismiss') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                {{-- employee --}}
                <x-ui.input-shell :label="__('leaves::sick_certificates.labels.employee')" :error="$errors->first('tabel_no')">
                    @if ($tabelNo !== '')
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-hairline bg-[#fafafa] px-3 py-2">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <x-avatar :name="$employeeName" size="sm" />
                                <div class="min-w-0 leading-tight">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $employeeName }}</p>
                                    <p class="hrm-num text-[11px] text-ink-faint">{{ __('leaves::sick_certificates.labels.tabel_no') }} {{ $tabelNo }}</p>
                                </div>
                            </div>
                            @unless ($employeeLocked)
                                <button type="button" wire:click="clearEmployee" class="text-[12px] font-medium text-ink-muted transition hover:text-ink">{{ __('leaves::sick_certificates.actions.change_employee') }}</button>
                            @endunless
                        </div>
                    @else
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="personnelSearch" :placeholder="__('leaves::sick_certificates.labels.search_employee')" autocomplete="off" />
                        @if (mb_strlen(trim($personnelSearch)) < 3)
                            <p class="text-[11.5px] text-ink-faint">{{ __('leaves::sick_certificates.labels.search_hint') }}</p>
                        @else
                            <div class="divide-y divide-hairline-subtle overflow-hidden rounded-xl border border-hairline">
                                @forelse ($this->employeeOptions as $option)
                                    <button type="button" wire:key="sick-employee-{{ $option->tabel_no }}" wire:click="chooseEmployee(@js($option->tabel_no))"
                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-left transition hover:bg-[#fafafa]">
                                        <x-avatar :name="$option->fullname" size="sm" />
                                        <span class="min-w-0 flex-1 truncate text-[13px] text-ink">{{ $option->fullname }}</span>
                                        <span class="hrm-num text-[11px] text-ink-faint">{{ $option->tabel_no }}</span>
                                    </button>
                                @empty
                                    <x-empty-inline class="rounded-none border-0">{{ __('leaves::sick_certificates.labels.no_employee_found') }}</x-empty-inline>
                                @endforelse
                            </div>
                        @endif
                    @endif
                </x-ui.input-shell>

                @if ($mode === 'extend' && $certificate)
                    <p class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-[12px] leading-snug text-sky-800">
                        {{ __('leaves::sick_certificates.panel.extend_note', ['number' => $certificate->fullNumber()]) }}
                    </p>
                @endif

                @if ($mode === 'close')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-hairline bg-[#fafafa] px-3 py-2">
                            <p class="hrm-eyebrow">{{ __('leaves::sick_certificates.labels.certificate') }}</p>
                            <p class="hrm-num mt-1 text-[13px] font-medium text-ink">№ {{ $certificate?->fullNumber() }}</p>
                        </div>
                        <div class="rounded-xl border border-hairline bg-[#fafafa] px-3 py-2">
                            <p class="hrm-eyebrow">{{ __('leaves::sick_certificates.labels.starts_at') }}</p>
                            <p class="hrm-num mt-1 text-[13px] font-medium text-ink">{{ filled($starts_at) ? \Carbon\CarbonImmutable::parse($starts_at)->format('d.m.Y') : '—' }}</p>
                        </div>
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.close_date')" :error="$errors->first('ends_at') ?: $errors->first('starts_at')">
                            <x-ui.date-input wire:model.live="ends_at" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('leaves::sick_certificates.labels.notes')">
                            <x-ui.textarea wire:model="notes" rows="2" />
                        </x-ui.input-shell>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.series')" :error="$errors->first('series')">
                            <x-ui.input class="uppercase" wire:model="series" maxlength="20" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('leaves::sick_certificates.labels.number')" :error="$errors->first('number')">
                            <x-ui.input class="hrm-num" wire:model="number" maxlength="40" />
                        </x-ui.input-shell>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.starts_at')" :error="$errors->first('starts_at')">
                            <x-ui.date-input wire:model.live="starts_at" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.ends_at')" :hint="__('leaves::sick_certificates.labels.ends_at_hint')" :error="$errors->first('ends_at')">
                            <x-ui.date-input wire:model.live="ends_at" />
                        </x-ui.input-shell>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.medical_institution')">
                            <x-ui.input wire:model="medical_institution" maxlength="255" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.doctor_name')">
                            <x-ui.input wire:model="doctor_name" maxlength="255" />
                        </x-ui.input-shell>
                    </div>

                    @if ($this->canViewDiagnosis)
                        <x-ui.input-shell :label="__('leaves::sick_certificates.labels.diagnosis')" :hint="__('leaves::sick_certificates.labels.diagnosis_hint')">
                            <x-ui.textarea wire:model="diagnosis" rows="2" autocomplete="off" />
                        </x-ui.input-shell>
                    @endif

                    <x-ui.input-shell :label="__('leaves::sick_certificates.labels.notes')">
                        <x-ui.textarea wire:model="notes" rows="2" />
                    </x-ui.input-shell>
                @endif

                @if ($warnings !== [])
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                        <p class="text-[12.5px] font-semibold text-amber-800">{{ __('leaves::sick_certificates.labels.overlaps_title') }}</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4 text-[12px] leading-snug text-amber-800">
                            @foreach ($warnings as $warning)
                                <li wire:key="sick-warning-{{ $loop->index }}">{{ $warning }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-1 text-[11.5px] text-amber-700">{{ __('leaves::sick_certificates.labels.overlaps_hint') }}</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-hairline-subtle bg-white px-5 py-3.5">
                <x-pill-button x-on:click="close()">{{ __('leaves::sick_certificates.actions.dismiss') }}</x-pill-button>
                <x-pill-button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ __('leaves::sick_certificates.actions.save') }}</x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
