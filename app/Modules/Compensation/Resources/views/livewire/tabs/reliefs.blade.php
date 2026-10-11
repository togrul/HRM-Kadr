<div class="contents">
    @if ($tabelNo)
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::reliefs.title') }}</h2>
                <p class="mt-0.5 text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::reliefs.hint') }}</p>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->reliefs as $relief)
                    @php
                        $amount = $this->amounts[$relief->category] ?? null;
                        $ended = $relief->valid_to !== null && $relief->valid_to->isPast();
                    @endphp
                    <div wire:key="compensation-relief-{{ $relief->id }}" class="flex items-start justify-between gap-3 px-4 py-3">
                        <div class="min-w-0 space-y-0.5">
                            <p class="text-[13px] font-medium text-ink">
                                {{ __('compensation::reliefs.categories.'.$relief->category) }}
                                @if ($amount !== null)
                                    <span class="hrm-num ml-1 text-ink-faint">· {{ number_format($amount, 2, ',', ' ') }} AZN</span>
                                @endif
                            </p>
                            @if ($relief->basis_detail)
                                <p class="text-[12px] text-ink-soft">{{ $relief->basis_detail }}</p>
                            @endif
                            <p class="text-[11.5px] text-ink-faint">
                                {{ __('compensation::reliefs.document_line', ['no' => $relief->document_no, 'date' => $relief->document_date?->format('d.m.Y') ?? '—', 'issuer' => $relief->document_issuer ?? '—']) }}
                                @if ($relief->document_path)
                                    <span class="px-0.5">·</span><a href="{{ route('compensation.tax-relief.document', $relief) }}" target="_blank" rel="noopener" class="font-medium underline underline-offset-2 hover:text-ink">{{ __('compensation::reliefs.open_document') }}</a>
                                @endif
                            </p>
                            <p class="hrm-num text-[11.5px] text-ink-faint">
                                {{ __('compensation::reliefs.dates_line', [
                                    'legal' => $relief->legal_from->format('d.m.Y'),
                                    'payroll' => $relief->payroll_from->format('d.m.Y'),
                                    'to' => $relief->valid_to?->format('d.m.Y') ?? __('compensation::reliefs.open_ended'),
                                ]) }}
                            </p>
                            @if ($relief->payroll_from->greaterThan($relief->legal_from))
                                <p class="text-[11.5px] text-amber-700">{{ __('compensation::reliefs.gap_warning', ['from' => $relief->legal_from->format('d.m.Y'), 'to' => $relief->payroll_from->copy()->subDay()->format('d.m.Y')]) }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if ($ended)
                                <x-small-badge mode="gray">{{ __('compensation::reliefs.ended') }}</x-small-badge>
                            @else
                                <x-small-badge mode="green" dot>{{ __('compensation::reliefs.active') }}</x-small-badge>
                            @endif
                            @if ($canManage)
                                @unless ($ended)
                                    <button type="button" wire:click="startEnd({{ $relief->id }})" title="{{ __('compensation::reliefs.end') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                @endunless
                                <button type="button" x-on:click="{{ $confirmDelete('deleteRelief('.$relief->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::reliefs.empty')" />
                    </div>
                @endforelse
            </div>
            <p class="border-t border-hairline-subtle bg-[#fafafa] px-4 py-2.5 text-[11.5px] leading-4 text-ink-faint">{{ __('compensation::reliefs.rule_102_7') }}</p>
        </section>
    @endif

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel === 'relief')
        <x-ui.side-panel title-id="compensation-panel-title" close-action="$wire.closePanel()" :close-label="__('compensation::dashboard.actions.close')" width="3xl">
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::reliefs.add')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <p class="text-[12px] leading-relaxed text-ink-faint">{{ __('compensation::reliefs.panel_hint') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="min-w-0 sm:col-span-2">
                        <x-ui.select-dropdown :label="__('compensation::reliefs.category')" mode="gray" direction="auto" :clearable="false" wire:model.live="reliefForm.category" :model="$this->categoryOptions" />
                        @error('reliefForm.category') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::reliefs.basis_detail')" :error="$errors->first('reliefForm.basis_detail')">
                        <x-ui.input wire:model="reliefForm.basis_detail" maxlength="160" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.document_no')" :error="$errors->first('reliefForm.document_no')">
                        <x-ui.input wire:model="reliefForm.document_no" maxlength="80" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.document_date')" :error="$errors->first('reliefForm.document_date')">
                        <x-ui.input type="date" wire:model="reliefForm.document_date" />
                    </x-ui.input-shell>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::reliefs.document_issuer')" :error="$errors->first('reliefForm.document_issuer')">
                        <x-ui.input wire:model="reliefForm.document_issuer" maxlength="160" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.status_from')" :error="$errors->first('reliefForm.status_from')">
                        <x-ui.input type="date" wire:model="reliefForm.status_from" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.submitted_on')" :error="$errors->first('reliefForm.submitted_on')">
                        <x-ui.input type="date" wire:model="reliefForm.submitted_on" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.valid_to')" :error="$errors->first('reliefForm.valid_to')">
                        <x-ui.input type="date" wire:model="reliefForm.valid_to" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.document')" :error="$errors->first('document')">
                        <input type="file" wire:model="document" class="block w-full text-[12.5px] text-ink-soft file:mr-3 file:rounded-lg file:border-0 file:bg-[#f4f4f5] file:px-3 file:py-1.5 file:text-[12px] file:font-medium file:text-ink" />
                    </x-ui.input-shell>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.note')">
                        <x-ui.input wire:model="reliefForm.note" />
                    </x-ui.input-shell>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveRelief'])
        </x-ui.side-panel>
    @elseif ($canManage && $panel === 'end')
        <x-ui.side-panel title-id="compensation-panel-title" close-action="$wire.closePanel()" :close-label="__('compensation::dashboard.actions.close')" width="2xl">
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::reliefs.end')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <p class="text-[12px] leading-relaxed text-ink-faint">{{ __('compensation::reliefs.end_hint') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell :label="__('compensation::reliefs.valid_to')" :error="$errors->first('endDate')">
                        <x-ui.input type="date" wire:model="endDate" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::reliefs.end_reason')" :error="$errors->first('endReason')">
                        <x-ui.input wire:model="endReason" maxlength="500" />
                    </x-ui.input-shell>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveEnd'])
        </x-ui.side-panel>
    @endif
</div>
