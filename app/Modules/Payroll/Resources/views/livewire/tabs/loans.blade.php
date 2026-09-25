<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.loans.title') }}</h2>

            <div class="w-full sm:w-[320px]">
                @if ($selectedTabelNo)
                    <div class="flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                        <span class="truncate text-[12.5px] font-semibold text-ink">{{ $selectedPersonnelLabel }}</span>
                        <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">{{ __('payroll::dashboard.actions.close') }}</button>
                    </div>
                @else
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="personnelSearch" x-on:focus="open = true" placeholder="{{ __('compensation::dashboard.actions.search_personnel') }}" />
                        @if (count($this->personnelResults))
                            <div x-show="open" class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-lg">
                                @foreach ($this->personnelResults as $res)
                                    <button type="button" wire:key="payroll-personnel-{{ $res['tabel_no'] }}" wire:click="selectPersonnel(@js($res['tabel_no']), @js($res['label']))" x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#f4f4f5] hover:text-ink">{{ $res['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if (! $selectedTabelNo)
            <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('payroll::dashboard.loans.select_personnel') }}</p>
        @else
            @if ($canManage)
                <div class="grid gap-3 border-b border-hairline-subtle px-4 py-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="min-w-0">
                        <x-ui.select-dropdown
                            :label="__('payroll::dashboard.fields.loan_type')"
                            mode="gray"
                            direction="auto"
                            wire:model.live="loanForm.type"
                            :model="[
                                ['id' => 'loan', 'label' => __('payroll::dashboard.loans.types.loan')],
                                ['id' => 'advance', 'label' => __('payroll::dashboard.loans.types.advance')],
                            ]"
                        />
                    </div>
                    <x-ui.input-shell :label="__('payroll::dashboard.fields.principal')" :error="$errors->first('loanForm.principal')">
                        <x-ui.input type="number" step="0.01" wire:model="loanForm.principal" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('payroll::dashboard.fields.monthly_installment')" :error="$errors->first('loanForm.monthly_installment')">
                        <x-ui.input type="number" step="0.01" wire:model="loanForm.monthly_installment" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('payroll::dashboard.fields.start_on')" :error="$errors->first('loanForm.start_on')">
                        <x-ui.input type="date" wire:model="loanForm.start_on" />
                    </x-ui.input-shell>
                    <div class="flex items-end">
                        <x-pill-button variant="primary" class="w-full justify-center" wire:click="saveLoan">{{ __('payroll::dashboard.actions.save') }}</x-pill-button>
                    </div>
                </div>
            @endif

            <x-table.tbl :headers="[
                __('payroll::dashboard.fields.loan_type'),
                __('payroll::dashboard.fields.principal'),
                __('payroll::dashboard.fields.monthly_installment'),
                __('payroll::dashboard.fields.remaining'),
                __('payroll::dashboard.fields.status'),
                __('payroll::dashboard.columns.actions'),
            ]">
                @forelse ($this->loans as $loan)
                    <tr wire:key="payroll-loan-{{ $loan->id }}">
                        <x-table.td standart-width>
                            <span class="{{ $chip }} {{ $loan->type === 'loan' ? 'bg-sky-50 text-sky-700' : 'bg-[#f4f4f5] text-ink-muted' }}">{{ __('payroll::dashboard.loans.types.'.$loan->type) }}</span>
                        </x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($loan->principal) }} {{ $loan->currency }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($loan->monthly_installment) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $money($loan->remaining) }}</span></x-table.td>
                        <x-table.td><span class="text-[13px] text-ink-muted">{{ __('payroll::dashboard.loans.statuses.'.$loan->status) }}</span></x-table.td>
                        <x-table.td :isButton="true">
                            @if ($canManage)
                                <button type="button" x-on:click="{{ $confirmDelete('deleteLoan('.$loan->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="{{ $delBtn }} ml-auto">{!! $delIcon !!}</button>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4">
                            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.loans.empty')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        @endif
    </section>
</div>
