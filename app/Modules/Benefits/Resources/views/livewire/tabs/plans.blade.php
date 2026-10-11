@php
    $check = 'rounded border-hairline text-ink focus:ring-[#e4e4e7]';
    $checkLabel = 'inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted';
    $multi = 'block h-32 w-full rounded-[10px] border border-hairline bg-white px-2 py-1.5 text-[12.5px] text-ink focus:border-ink focus:outline-none focus:ring-0';
@endphp

<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.plans.title') }}</h2>
            <div class="w-full sm:max-w-[280px]">
                <x-ui.input icon="search" wire:model.live.debounce.300ms="planSearch" placeholder="{{ __('benefits::dashboard.actions.search') }}" />
            </div>
        </div>

        <x-table.tbl :headers="[
            __('benefits::dashboard.columns.plan'),
            __('benefits::dashboard.columns.type'),
            __('benefits::dashboard.columns.monthly_cost'),
            __('benefits::dashboard.columns.tax'),
            __('benefits::dashboard.columns.contract'),
            __('benefits::dashboard.columns.enrollees'),
            __('benefits::dashboard.columns.actions'),
        ]">
            @forelse ($this->plans as $plan)
                <tr wire:key="benefits-plan-{{ $plan->id }}">
                    <x-table.td standart-width>
                        <p class="max-w-[240px] truncate text-[13px] font-medium text-ink">{{ $plan->name }}</p>
                        <p class="hrm-num truncate text-[11px] uppercase tracking-[0.04em] text-ink-faint">{{ $plan->code }}@if ($plan->provider) · {{ $plan->provider }}@endif</p>
                    </x-table.td>
                    <x-table.td>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge mode="sky">{{ __('benefits::dashboard.types.'.$plan->type) }}</x-small-badge>
                            @unless ($plan->is_active)
                                <x-small-badge mode="secondary">{{ __('benefits::dashboard.statuses.inactive') }}</x-small-badge>
                            @endunless
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <p class="hrm-num text-[13px] text-ink">{{ $money($plan->monthly_cost) }} {{ $plan->currency }}</p>
                        <p class="text-[11px] text-ink-faint">{{ __('benefits::dashboard.plans.employer_share', ['percent' => rtrim(rtrim((string) $plan->employer_share_percent, '0'), '.')]) }}</p>
                    </x-table.td>
                    <x-table.td>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge :mode="$plan->taxable ? 'rose' : 'green'">{{ $plan->taxable ? __('benefits::dashboard.tax.taxable') : __('benefits::dashboard.tax.exempt') }}</x-small-badge>
                            @if ($plan->affects_social)
                                <x-small-badge mode="secondary">{{ __('benefits::dashboard.tax.social') }}</x-small-badge>
                            @endif
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[12.5px] text-ink-soft">{{ $plan->contract_start?->format('d.m.Y') ?? '—' }} – {{ $plan->contract_end?->format('d.m.Y') ?? '—' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[13px] text-ink">{{ (int) $plan->getAttribute('active_enrollments_count') }}</span>
                    </x-table.td>
                    <x-table.td :isButton="true">
                        @if ($canManage)
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" wire:click="editPlan({{ $plan->id }})" title="{{ __('benefits::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button" x-on:click="{{ $confirm('deletePlan('.$plan->id.')') }}" title="{{ __('benefits::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            </div>
                        @endif
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10">
                        <x-ui.empty-state icon="icons.gift-icon" :title="__('benefits::dashboard.plans.empty')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>

        <x-pagination :paginator="$this->plans" :unit="__('benefits::dashboard.summary.plans')" />
    </section>

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel === 'plan')
        <x-ui.side-panel
            title-id="benefits-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('benefits::dashboard.actions.close')"
            width="3xl"
        >
            @include('benefits::livewire.tabs.partials.panel-header', ['panelTitle' => __('benefits::dashboard.plans.form_title')])

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell :label="__('benefits::dashboard.fields.code')" :error="$errors->first('planForm.code')">
                        <x-ui.input wire:model="planForm.code" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('benefits::dashboard.fields.name')" :error="$errors->first('planForm.name')">
                        <x-ui.input wire:model="planForm.name" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('benefits::dashboard.fields.type')" :error="$errors->first('planForm.type')">
                        <x-ui.select wire:model.live="planForm.type">
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ __('benefits::dashboard.types.'.$type) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('benefits::dashboard.fields.provider')">
                        <x-ui.input wire:model="planForm.provider" />
                    </x-ui.input-shell>
                    <x-ui.input-shell class="sm:col-span-2" :label="__('benefits::dashboard.fields.description')">
                        <x-ui.input wire:model="planForm.description" />
                    </x-ui.input-shell>
                </div>

                <div>
                    <p class="hrm-eyebrow">{{ __('benefits::dashboard.plans.cost_section') }}</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.monthly_cost')" :error="$errors->first('planForm.monthly_cost')">
                            <x-ui.input type="number" step="0.01" wire:model="planForm.monthly_cost" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.employer_share_percent')" :error="$errors->first('planForm.employer_share_percent')">
                            <x-ui.input type="number" step="0.01" wire:model="planForm.employer_share_percent" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.currency')" :error="$errors->first('planForm.currency')">
                            <x-ui.input class="uppercase" wire:model="planForm.currency" />
                        </x-ui.input-shell>
                    </div>
                    <label class="{{ $checkLabel }} mt-3">
                        <input type="checkbox" wire:model.live="planForm.allows_dependents" class="{{ $check }}" />
                        {{ __('benefits::dashboard.fields.allows_dependents') }}
                    </label>
                    @if ($planForm['allows_dependents'] ?? false)
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            <x-ui.input-shell :label="__('benefits::dashboard.fields.dependent_monthly_cost')" :error="$errors->first('planForm.dependent_monthly_cost')">
                                <x-ui.input type="number" step="0.01" wire:model="planForm.dependent_monthly_cost" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('benefits::dashboard.fields.dependent_employer_share_percent')" :error="$errors->first('planForm.dependent_employer_share_percent')">
                                <x-ui.input type="number" step="0.01" wire:model="planForm.dependent_employer_share_percent" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('benefits::dashboard.fields.max_dependents')" :error="$errors->first('planForm.max_dependents')">
                                <x-ui.input type="number" wire:model="planForm.max_dependents" />
                            </x-ui.input-shell>
                        </div>
                    @endif
                </div>

                <div>
                    <p class="hrm-eyebrow">{{ __('benefits::dashboard.plans.tax_section') }}</p>
                    <div class="mt-2 flex flex-wrap gap-4">
                        <label class="{{ $checkLabel }}">
                            <input type="checkbox" wire:model="planForm.taxable" class="{{ $check }}" />
                            {{ __('benefits::dashboard.fields.taxable') }}
                        </label>
                        <label class="{{ $checkLabel }}">
                            <input type="checkbox" wire:model="planForm.affects_social" class="{{ $check }}" />
                            {{ __('benefits::dashboard.fields.affects_social') }}
                        </label>
                    </div>
                    <x-ui.input-shell class="mt-3" :label="__('benefits::dashboard.fields.tax_basis')">
                        <x-ui.select wire:model.live="planForm.tax_basis">
                            @foreach ($bases as $basis)
                                <option value="{{ $basis }}">{{ __('benefits::dashboard.tax_basis.'.$basis.'.title') }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>
                    @if (in_array($planForm['tax_basis'] ?? '', $bases, true))
                        <div class="mt-2 rounded-[10px] border border-hairline bg-[#fafafa] px-3 py-2.5 text-[12px] leading-relaxed text-ink-muted">
                            <p>{{ __('benefits::dashboard.tax_basis.'.$planForm['tax_basis'].'.body') }}</p>
                            <a href="{{ __('benefits::dashboard.tax_basis.'.$planForm['tax_basis'].'.url') }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block font-medium text-ink underline decoration-[#d4d4d8] underline-offset-2 hover:decoration-ink">{{ __('benefits::dashboard.tax_basis.source') }}</a>
                        </div>
                    @endif
                </div>

                <div>
                    <p class="hrm-eyebrow">{{ __('benefits::dashboard.plans.contract_section') }}</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.contract_start')" :error="$errors->first('planForm.contract_start')">
                            <x-ui.input type="date" wire:model="planForm.contract_start" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.contract_end')" :error="$errors->first('planForm.contract_end')">
                            <x-ui.input type="date" wire:model="planForm.contract_end" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.renewal_window_days')" :error="$errors->first('planForm.renewal_window_days')">
                            <x-ui.input type="number" wire:model="planForm.renewal_window_days" />
                        </x-ui.input-shell>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4">
                        @foreach (['auto_renew', 'self_service', 'is_active'] as $flag)
                            <label class="{{ $checkLabel }}">
                                <input type="checkbox" wire:model="planForm.{{ $flag }}" class="{{ $check }}" />
                                {{ __('benefits::dashboard.fields.'.$flag) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="hrm-eyebrow">{{ __('benefits::dashboard.plans.eligibility_section') }}</p>
                    <p class="mt-1 text-[12px] text-ink-faint">{{ __('benefits::dashboard.plans.eligibility_help') }}</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <label class="{{ $checkLabel }} sm:col-span-2">
                            <input type="checkbox" wire:model="planForm.eligibility.after_probation" class="{{ $check }}" />
                            {{ __('benefits::dashboard.fields.after_probation') }}
                        </label>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.min_service_months')" :error="$errors->first('planForm.eligibility.min_service_months')">
                            <x-ui.input type="number" wire:model="planForm.eligibility.min_service_months" />
                        </x-ui.input-shell>
                        <div>
                            <p class="text-[12px] font-medium text-ink-muted">{{ __('benefits::dashboard.fields.contract_types') }}</p>
                            <div class="mt-2 flex flex-wrap gap-4">
                                @foreach (['fixed', 'indefinite'] as $contractType)
                                    <label class="{{ $checkLabel }}">
                                        <input type="checkbox" value="{{ $contractType }}" wire:model="planForm.eligibility.contract_types" class="{{ $check }}" />
                                        {{ __('benefits::dashboard.contract_types.'.$contractType) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-[12px] font-medium text-ink-muted">{{ __('benefits::dashboard.fields.vtisk_categories') }}</p>
                            <div class="mt-2 flex flex-wrap gap-4">
                                @foreach (['rehber', 'mutexessis', 'texniki_icraci', 'fehle'] as $category)
                                    <label class="{{ $checkLabel }}">
                                        <input type="checkbox" value="{{ $category }}" wire:model="planForm.eligibility.vtisk_categories" class="{{ $check }}" />
                                        {{ __('benefits::dashboard.vtisk_categories.'.$category) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.position_ids')">
                            <select multiple wire:model="planForm.eligibility.position_ids" class="{{ $multi }}">
                                @foreach ($this->positionOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('benefits::dashboard.fields.structure_ids')">
                            <select multiple wire:model="planForm.eligibility.structure_ids" class="{{ $multi }}">
                                @foreach ($this->structureOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                </div>
            </div>

            @include('benefits::livewire.tabs.partials.panel-footer', ['save' => 'savePlan'])
        </x-ui.side-panel>
    @endif
</div>
