@php
    $options = \App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::class;
@endphp

<div class="space-y-6">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(320px,0.8fr)]">
        <div class="space-y-4">
            <div class="grid gap-1 lg:grid-cols-2 2xl:grid-cols-4">
                <x-ui.input-shell :label="__('personnel::portfolio.fields.search')" labelClass="tracking-tight text-zinc-500"><input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::portfolio.messages.search_placeholder') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" /></x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.status')" labelClass="tracking-tight text-zinc-500">
                    <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                        <option value="all">Hamısı</option>
                        @foreach (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::verificationStatuses() as $status)
                            <option value="{{ $status }}">{{ __('personnel::portfolio.status.'.$status) }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_from')" labelClass="tracking-tight text-zinc-500"><input wire:model.live="dateFrom" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" /></x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_to')" labelClass="tracking-tight text-zinc-500"><input wire:model.live="dateTo" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" /></x-ui.input-shell>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">{{ __('personnel::portfolio.tabs.projects') }}</p>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportExcel" wire:target="exportExcel" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_excel') }}</x-ui.async-button>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportCsv" wire:target="exportCsv" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_csv') }}</x-ui.async-button>
                    @can('manage-personnel-project-records')
                        <x-ui.async-button variant="primary" wire:click="openCreate" wire:target="openCreate" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.add_project') }}</x-ui.async-button>
                    @endcan
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->records as $record)
                    <div class="rounded-[24px] border {{ $record->verification_status === 'pending' ? 'border-amber-200 bg-amber-50/40 shadow-sm shadow-amber-100/40' : 'border-zinc-200 bg-white' }} p-4">
                        <div class="space-y-3">
                            <div class="rounded-[22px] border border-zinc-200 bg-zinc-50/80 px-4 py-3.5">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $record->project_name }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-notification.chip mode="{{ $record->verification_status === 'verified' ? 'emerald' : ($record->verification_status === 'rejected' ? 'rose' : 'muted') }}">{{ __('personnel::portfolio.status.'.$record->verification_status) }}</x-notification.chip>
                                <x-notification.chip mode="sky">{{ $record->role_title }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ __('personnel::portfolio.options.project_type.'.$record->project_type) }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ optional($record->start_date)->format('d.m.Y') }}</x-notification.chip>
                                @if($record->end_date)
                                    <x-notification.chip mode="muted">{{ optional($record->end_date)->format('d.m.Y') }}</x-notification.chip>
                                @elseif($record->is_ongoing)
                                    <x-notification.chip mode="muted">{{ __('personnel::portfolio.fields.is_ongoing') }}</x-notification.chip>
                                @endif
                            </div>

                            <div class="border-t border-zinc-100"></div>

                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-2">
                                <x-ui.async-button variant="secondary" size="sm" fullWidth="true" wire:click="selectRecord({{ $record->id }})" wire:target="selectRecord({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.show_details') }}</x-ui.async-button>
                                @can('manage-personnel-project-records')
                                    <x-ui.async-button variant="primary" size="sm" fullWidth="true" wire:click="edit({{ $record->id }})" wire:target="edit({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.edit') }}</x-ui.async-button>
                                @endcan
                            </div>

                            @canany(['verify-professional-portfolio-records', 'verify-personnel-project-records'])
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-2">
                                    @if ($record->verification_status !== 'verified')
                                        <x-ui.async-button variant="success" size="sm" fullWidth="true" wire:click="verify({{ $record->id }})" wire:target="verify({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.verify') }}</x-ui.async-button>
                                    @endif
                                    @if ($record->verification_status !== 'rejected')
                                        <x-ui.async-button variant="danger" size="sm" fullWidth="true" wire:click="reject({{ $record->id }})" wire:target="reject({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.reject') }}</x-ui.async-button>
                                    @endif
                                </div>
                            @endcanany
                        </div>

                        @if ($selectedId === $record->id && $this->selectedRecord)
                            <div class="mt-4 space-y-4 border-t border-zinc-100 pt-4">
                                <x-professional-portfolio.verification-panel :status="$this->selectedRecord->verification_status" :verifier="$this->selectedRecord->verifier?->name" :verified-at="optional($this->selectedRecord->verified_at)?->format('d.m.Y H:i')" />
                                <div class="space-y-4">
                                    <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-4">
                                        <div class="space-y-4">
                                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.responsibility_summary') }}</p>
                                                <p class="mt-1 text-sm leading-6 text-zinc-700">{{ $this->selectedRecord->responsibility_summary }}</p>
                                            </div>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.project_type') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ __('personnel::portfolio.options.project_type.'.$this->selectedRecord->project_type) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.partner_organizations') }}</p>
                                                    <p class="mt-1 text-sm leading-6 text-zinc-700">{{ $this->selectedRecord->partner_organizations ?: '—' }}</p>
                                                </div>
                                            </div>
                                            @if (filled($this->selectedRecord->impact_summary) || filled($this->selectedRecord->outcome_summary))
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    @if (filled($this->selectedRecord->impact_summary))
                                                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.impact_summary') }}</p>
                                                            <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->impact_summary }}</p>
                                                        </div>
                                                    @endif
                                                    @if (filled($this->selectedRecord->outcome_summary))
                                                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.outcome_summary') }}</p>
                                                            <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->outcome_summary }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <x-professional-portfolio.attachment-row :attachment="$this->selectedRecord->evidenceAttachment" :label="__('personnel::portfolio.fields.evidence')" />
                                        <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.sponsor_unit') }}</p>
                                            <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->sponsorUnit?->name ?: '—' }}</p>
                                            @if (filled($this->selectedRecord->reference_url))
                                                <p class="mt-3"><a href="{{ $this->selectedRecord->reference_url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-zinc-700 underline">{{ __('personnel::portfolio.actions.open_link') }}</a></p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-5 py-8 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
                @endforelse
            </div>
        </div>

        @if ($showForm)
            <div class="rounded-[24px] border border-zinc-200 bg-zinc-50 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $editingId ? __('personnel::portfolio.actions.edit') : __('personnel::portfolio.actions.add_project') }}</h3>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="cancelForm" wire:target="cancelForm" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.cancel') }}</x-ui.async-button>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.project_name')" :error="$errors->first('form.project_name')"><input wire:model.defer="form.project_name" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.project_code')" :error="$errors->first('form.project_code')"><input wire:model.defer="form.project_code" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.project_type')">
                            <select wire:model.defer="form.project_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::projectTypes() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.project_type.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.role_title')" :error="$errors->first('form.role_title')"><input wire:model.defer="form.role_title" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.responsibility_summary')" :error="$errors->first('form.responsibility_summary')"><textarea wire:model.defer="form.responsibility_summary" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.team_name')"><input wire:model.defer="form.team_name" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.sponsor_unit')" :error="$errors->first('form.sponsor_unit_id')">
                            <select wire:model.defer="form.sponsor_unit_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                <option value="">—</option>
                                @foreach ($this->sponsorUnitOptions as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.start_date')" :error="$errors->first('form.start_date')"><input wire:model.defer="form.start_date" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.end_date')" :error="$errors->first('form.end_date')"><input wire:model.defer="form.end_date" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                        <input wire:model.live="form.is_ongoing" type="checkbox" class="rounded border-zinc-300 text-zinc-950" />
                        <span>{{ __('personnel::portfolio.fields.is_ongoing') }}</span>
                    </label>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.partner_organizations')"><textarea wire:model.defer="form.partner_organizations" rows="2" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.outcome_summary')"><textarea wire:model.defer="form.outcome_summary" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.impact_summary')"><textarea wire:model.defer="form.impact_summary" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.reference_url')" :error="$errors->first('form.reference_url')"><input wire:model.defer="form.reference_url" type="url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                        <x-ui.file-upload-shell wire:model="evidenceUpload" :label="__('personnel::portfolio.fields.evidence')" :error="$errors->first('evidenceUpload')" :upload="$evidenceUpload" :existing-name="$editingId && $this->selectedRecord?->evidenceAttachment ? $this->selectedRecord->evidenceAttachment->original_name : null" />
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.notes')"><textarea wire:model.defer="form.notes" rows="2" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                </div>
                <div class="mt-4">
                    <x-ui.async-button variant="primary" fullWidth="true" wire:click="save" wire:target="save" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.save_record') }}</x-ui.async-button>
                </div>
            </div>
        @endif
    </div>
</div>
