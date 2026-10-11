@php
    $kindOptions = collect(\App\Models\EnforcementDocument::KINDS)->map(fn ($kind) => ['id' => $kind, 'label' => __('payroll::enforcement.kinds.'.$kind)])->all();
    $issuerOptions = collect(\App\Models\EnforcementDocument::ISSUER_TYPES)->map(fn ($type) => ['id' => $type, 'label' => __('payroll::enforcement.issuer_types.'.$type)])->all();
    $methodOptions = collect(\App\Models\EnforcementDocument::METHODS)->map(fn ($method) => ['id' => $method, 'label' => __('payroll::enforcement.methods.'.$method)])->all();
@endphp

<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::enforcement.title') }}</h2>

            <div class="w-full sm:w-[320px]">
                @if ($selectedTabelNo)
                    <div class="flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                        <span class="truncate text-[12.5px] font-semibold text-ink">{{ $selectedPersonnelLabel }}</span>
                        <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">{{ __('payroll::dashboard.actions.clear') }}</button>
                    </div>
                @else
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="personnelSearch" x-on:focus="open = true" placeholder="{{ __('payroll::dashboard.loans.search_personnel') }}" aria-label="{{ __('payroll::dashboard.loans.search_personnel') }}" />
                        @if (count($this->personnelResults))
                            <div x-show="open" class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-lg">
                                @foreach ($this->personnelResults as $res)
                                    <button type="button" wire:key="payroll-enforcement-personnel-{{ $res['tabel_no'] }}" wire:click="selectPersonnel(@js($res['tabel_no']), @js($res['label']))" x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#f4f4f5] hover:text-ink">{{ $res['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <p class="border-b border-hairline-subtle px-4 py-2.5 text-[11.5px] leading-relaxed text-ink-faint">{{ __('payroll::enforcement.rules') }}</p>

        @if (! $selectedTabelNo)
            <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('payroll::dashboard.loans.select_personnel') }}</p>
        @else
            @if ($canManage)
                <div class="flex justify-end border-b border-hairline-subtle px-4 py-2.5">
                    @if ($formOpen)
                        <x-pill-button variant="secondary" wire:click="closeForm">{{ __('payroll::dashboard.actions.close') }}</x-pill-button>
                    @else
                        <x-pill-button variant="primary" wire:click="openForm">{{ __('payroll::enforcement.add') }}</x-pill-button>
                    @endif
                </div>
            @endif

            @if ($canManage && $formOpen)
                <div class="grid gap-3 border-b border-hairline-subtle bg-[#fafafa] px-4 py-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="min-w-0 sm:col-span-2">
                        <x-ui.select-dropdown :label="__('payroll::enforcement.fields.kind')" mode="gray" direction="auto" :clearable="false" wire:model.live="documentForm.kind" :model="$kindOptions" />
                    </div>
                    <x-ui.input-shell :label="__('payroll::enforcement.fields.priority')" :error="$errors->first('documentForm.priority')">
                        <x-ui.input type="number" min="1" max="5" wire:model="documentForm.priority" placeholder="{{ \App\Modules\Payroll\Application\Services\EnforcementService::defaultPriority((string) ($documentForm['kind'] ?? 'other')) }}" />
                    </x-ui.input-shell>
                    <div class="min-w-0">
                        <x-ui.select-dropdown :label="__('payroll::enforcement.fields.issuer_type')" mode="gray" direction="auto" :clearable="false" wire:model.live="documentForm.issuer_type" :model="$issuerOptions" />
                    </div>
                    @foreach (['issuer_name', 'officer_name', 'document_no', 'recipient_name', 'recipient_account', 'recipient_details'] as $field)
                        <x-ui.input-shell :label="__('payroll::enforcement.fields.'.$field)" :error="$errors->first('documentForm.'.$field)">
                            <x-ui.input wire:model="documentForm.{{ $field }}" />
                        </x-ui.input-shell>
                    @endforeach
                    @foreach (['document_date', 'received_on', 'starts_on', 'ends_on'] as $field)
                        <x-ui.input-shell :label="__('payroll::enforcement.fields.'.$field)" :error="$errors->first('documentForm.'.$field)">
                            <x-ui.input type="date" wire:model="documentForm.{{ $field }}" />
                        </x-ui.input-shell>
                    @endforeach
                    <div class="min-w-0">
                        <x-ui.select-dropdown :label="__('payroll::enforcement.fields.method')" mode="gray" direction="auto" :clearable="false" wire:model.live="documentForm.method" :model="$methodOptions" />
                    </div>
                    @if (($documentForm['method'] ?? 'percent') === 'fixed')
                        <x-ui.input-shell :label="__('payroll::enforcement.fields.fixed_amount')" :error="$errors->first('documentForm.fixed_amount')">
                            <x-ui.input type="number" step="0.01" wire:model="documentForm.fixed_amount" />
                        </x-ui.input-shell>
                    @else
                        <x-ui.input-shell :label="__('payroll::enforcement.fields.percent')" :error="$errors->first('documentForm.percent')">
                            <x-ui.input type="number" step="0.01" wire:model="documentForm.percent" />
                        </x-ui.input-shell>
                    @endif
                    <x-ui.input-shell :label="__('payroll::enforcement.fields.total_debt')" :error="$errors->first('documentForm.total_debt')">
                        <x-ui.input type="number" step="0.01" wire:model="documentForm.total_debt" />
                    </x-ui.input-shell>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('payroll::enforcement.fields.note')">
                        <x-ui.input wire:model="documentForm.note" />
                    </x-ui.input-shell>
                    <div class="flex items-end justify-end sm:col-span-2 xl:col-span-4">
                        <x-pill-button variant="primary" wire:click="saveDocument">{{ __('payroll::dashboard.actions.save') }}</x-pill-button>
                    </div>
                </div>
            @endif

            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->documents as $document)
                    <div wire:key="payroll-enforcement-doc-{{ $document->id }}" class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-[13px] font-medium text-ink">
                                    {{ __('payroll::enforcement.kinds.'.$document->kind) }} · № {{ $document->document_no }}
                                    <span class="{{ $chip }} ml-1 {{ $document->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}">{{ __('payroll::enforcement.statuses.'.$document->status) }}</span>
                                </p>
                                <p class="text-[11.5px] text-ink-faint">{{ __('payroll::enforcement.summary_line', [
                                    'priority' => $document->priority,
                                    'issuer' => $document->issuer_name,
                                    'recipient' => $document->recipient_name,
                                    'from' => $document->starts_on->format('d.m.Y'),
                                    'to' => $document->ends_on?->format('d.m.Y') ?? '—',
                                ]) }}</p>
                                <p class="hrm-num text-[12px] text-ink-soft">
                                    {{ $document->method === 'fixed' ? __('payroll::enforcement.fixed_line', ['amount' => $money($document->fixed_amount)]) : __('payroll::enforcement.percent_line', ['percent' => rtrim(rtrim((string) $document->percent, '0'), '.')]) }}
                                    @if ($document->total_debt !== null)
                                        · {{ __('payroll::enforcement.debt_line', ['total' => $money($document->total_debt), 'remaining' => $money($document->remaining)]) }}
                                    @endif
                                    · {{ __('payroll::enforcement.deducted_line', ['amount' => $money($this->deductedTotal($document))]) }}
                                </p>
                                @if ($document->deductions->isNotEmpty())
                                    <ul class="mt-1 space-y-0.5 text-[11.5px] text-ink-faint">
                                        @foreach ($document->deductions->take(6) as $deduction)
                                            <li wire:key="payroll-enforcement-ded-{{ $deduction->id }}" class="hrm-num">
                                                {{ $deduction->run?->period?->code ?? $deduction->paid_on->format('m.Y') }}: {{ $money($deduction->amount) }}
                                                @if ((float) $deduction->requested_amount > (float) $deduction->amount + 0.001)
                                                    <span class="text-amber-700">({{ __('payroll::enforcement.capped_history', ['requested' => $money($deduction->requested_amount)]) }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            @if ($canManage)
                                <div class="flex shrink-0 items-center gap-1.5">
                                    @if ($document->status === 'active')
                                        <x-pill-button variant="secondary" wire:click="startStatus({{ $document->id }}, 'suspended')">{{ __('payroll::enforcement.suspend') }}</x-pill-button>
                                        <x-pill-button variant="secondary" wire:click="startStatus({{ $document->id }}, 'closed')">{{ __('payroll::enforcement.close') }}</x-pill-button>
                                    @else
                                        <x-pill-button variant="secondary" wire:click="startStatus({{ $document->id }}, 'active')">{{ __('payroll::enforcement.resume') }}</x-pill-button>
                                    @endif
                                    @if ($document->deductions->isEmpty())
                                        <button type="button" x-on:click="{{ $confirmDelete('deleteDocument('.$document->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($statusDocumentId === $document->id)
                            <div class="mt-2 flex flex-wrap items-end gap-2 rounded-xl border border-hairline bg-[#fafafa] p-3">
                                <x-ui.input-shell class="min-w-[260px] flex-1" :label="__('payroll::enforcement.status_reason')" :error="$errors->first('statusReason')">
                                    <x-ui.input wire:model="statusReason" maxlength="500" />
                                </x-ui.input-shell>
                                <x-pill-button variant="primary" x-on:click="{{ $confirm('amber', 'enforcement_status', 'save', 'saveStatus()') }}">{{ __('payroll::enforcement.statuses.'.$statusTarget) }}</x-pill-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-6">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::enforcement.empty')" />
                    </div>
                @endforelse
            </div>
        @endif
    </section>

    {{-- ===================== icra məmuruna göndəriləcək siyahı ===================== --}}
    @if ($this->canViewAmounts())
        <section class="mt-4 overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::enforcement.report.title') }}</h2>
                    <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('payroll::enforcement.report.hint') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-[220px]">
                        <x-ui.select-dropdown :aria-label="__('payroll::enforcement.report.run')" mode="gray" direction="auto" wire:model.live="reportRunId" :model="$this->lockedRuns->map(fn ($run) => ['id' => $run->id, 'label' => ($run->period->code ?? '—').' · '.__('payroll::dashboard.run_types.'.$run->run_type)])->values()->all()" :placeholder="__('payroll::enforcement.report.run')" />
                    </div>
                    @if ($reportRunId && $this->canExport())
                        <x-pill-button variant="secondary" wire:click="exportReport">{{ __('payroll::enforcement.report.export') }}</x-pill-button>
                    @endif
                </div>
            </div>

            @if ($this->reportRows === [])
                <div class="px-4 py-6">
                    <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::enforcement.report.empty')" />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[12px]">
                        <thead class="bg-[#fafafa] text-[10.5px] uppercase tracking-wide text-ink-faint">
                            <tr>
                                @foreach (['full_name', 'kind', 'issuer', 'document_no', 'recipient', 'amount', 'remaining'] as $col)
                                    <th class="px-3 py-2">{{ __('payroll::enforcement.report.cols.'.$col) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline-subtle">
                            @foreach ($this->reportRows as $i => $row)
                                <tr wire:key="payroll-enforcement-report-{{ $i }}">
                                    <td class="px-3 py-2 text-ink">{{ $row['full_name'] }} <span class="text-ink-faint">· {{ $row['tabel_no'] }}</span></td>
                                    <td class="px-3 py-2 text-ink-soft">{{ $row['kind'] }}</td>
                                    <td class="px-3 py-2 text-ink-soft">{{ $row['issuer'] }}</td>
                                    <td class="px-3 py-2 text-ink-soft">{{ $row['document_no'] }}</td>
                                    <td class="px-3 py-2 text-ink-soft">{{ $row['recipient'] }}</td>
                                    <td class="hrm-num px-3 py-2 font-medium text-ink">{{ $money($row['amount']) }}</td>
                                    <td class="hrm-num px-3 py-2 text-ink-soft">{{ $row['remaining'] === '' ? '—' : $money($row['remaining']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
