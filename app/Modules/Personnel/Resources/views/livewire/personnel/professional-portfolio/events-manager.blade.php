@php
    $options = \App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::class;
@endphp

<div class="space-y-6">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(320px,0.8fr)]">
        <div class="space-y-4">
            <div class="grid gap-1 lg:grid-cols-2 2xl:grid-cols-4">
                <x-ui.input-shell :label="__('personnel::portfolio.fields.search')" :error="$errors->first('search')" labelClass="tracking-tight text-zinc-500">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::portfolio.messages.search_placeholder') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.status')" labelClass="tracking-tight text-zinc-500">
                    <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                        <option value="all">Hamısı</option>
                        @foreach (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::verificationStatuses() as $status)
                            <option value="{{ $status }}">{{ __('personnel::portfolio.status.'.$status) }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_from')" labelClass="tracking-tight text-zinc-500">
                    <input wire:model.live="dateFrom" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_to')" labelClass="tracking-tight text-zinc-500">
                    <input wire:model.live="dateTo" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" />
                </x-ui.input-shell>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">{{ __('personnel::portfolio.tabs.events') }}</p>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportExcel" wire:target="exportExcel" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_excel') }}</x-ui.async-button>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportCsv" wire:target="exportCsv" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_csv') }}</x-ui.async-button>
                    @can('manage-personnel-event-records')
                        <x-ui.async-button variant="primary" wire:click="openCreate" wire:target="openCreate" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.add_event') }}</x-ui.async-button>
                    @endcan
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->records as $record)
                    <div class="rounded-[24px] border {{ $record->verification_status === 'pending' ? 'border-amber-200 bg-amber-50/40 shadow-sm shadow-amber-100/40' : 'border-zinc-200 bg-white' }} p-4">
                        <div class="space-y-3">
                            <div class="rounded-[22px] border border-zinc-200 bg-zinc-50/80 px-4 py-3.5">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $record->title }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-notification.chip mode="{{ $record->verification_status === 'verified' ? 'emerald' : ($record->verification_status === 'rejected' ? 'rose' : 'muted') }}">{{ __('personnel::portfolio.status.'.$record->verification_status) }}</x-notification.chip>
                                <x-notification.chip mode="sky">{{ __('personnel::portfolio.options.participation_role.'.$record->participation_role) }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ __('personnel::portfolio.options.event_type.'.$record->event_type) }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ optional($record->start_date)->format('d.m.Y') }}</x-notification.chip>
                                @if (filled($record->organizer_name))
                                    <x-notification.chip mode="muted">{{ $record->organizer_name }}</x-notification.chip>
                                @endif
                                @if (filled($record->country?->currentCountryTranslations?->title))
                                    <x-notification.chip mode="muted">{{ $record->country->currentCountryTranslations->title }}</x-notification.chip>
                                @endif
                            </div>

                            <div class="border-t border-zinc-100"></div>

                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                <x-ui.async-button variant="secondary" size="sm" fullWidth="true" wire:click="selectRecord({{ $record->id }})" wire:target="selectRecord({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.show_details') }}</x-ui.async-button>
                                @can('manage-personnel-event-records')
                                    <x-ui.async-button variant="primary" size="sm" fullWidth="true" wire:click="edit({{ $record->id }})" wire:target="edit({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.edit') }}</x-ui.async-button>
                                @endcan
                                @canany(['verify-professional-portfolio-records', 'verify-personnel-event-records'])
                                    @if ($record->verification_status !== 'verified')
                                        <x-ui.async-button variant="success" size="sm" fullWidth="true" wire:click="verify({{ $record->id }})" wire:target="verify({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.verify') }}</x-ui.async-button>
                                    @endif
                                    @if ($record->verification_status !== 'rejected')
                                        <x-ui.async-button variant="danger" size="sm" fullWidth="true" wire:click="reject({{ $record->id }})" wire:target="reject({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.reject') }}</x-ui.async-button>
                                    @endif
                                @endcanany
                            </div>
                        </div>

                        @if ($selectedId === $record->id && $this->selectedRecord)
                            <div class="mt-4 space-y-4 border-t border-zinc-100 pt-4">
                                <x-professional-portfolio.verification-panel :status="$this->selectedRecord->verification_status" :verifier="$this->selectedRecord->verifier?->name" :verified-at="optional($this->selectedRecord->verified_at)?->format('d.m.Y H:i')" />
                                <div class="space-y-4">
                                    <div class="space-y-4">
                                        <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-4">
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.topic') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->topic ?: '—' }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.location') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->location ?: '—' }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.country') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ $this->selectedRecord->country?->currentCountryTranslations?->title ?: '—' }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.attendance_format') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ __('personnel::portfolio.options.attendance_format.'.$this->selectedRecord->attendance_format) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.strategic_level') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ __('personnel::portfolio.options.strategic_level.'.$this->selectedRecord->strategic_level) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @if (filled($this->selectedRecord->result_summary) || filled($this->selectedRecord->impact_summary) || filled($this->selectedRecord->hr_value_reason) || filled($this->selectedRecord->source_url))
                                            <div class="rounded-[24px] border border-zinc-200 bg-white p-4">
                                                <div class="space-y-4 text-sm leading-6 text-zinc-700">
                                                    @if (filled($this->selectedRecord->hr_value_reason))
                                                        <div>
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.hr_value_reason') }}</p>
                                                            <p class="mt-1">{{ $this->selectedRecord->hr_value_reason }}</p>
                                                        </div>
                                                    @endif
                                                    @if (filled($this->selectedRecord->result_summary))
                                                        <div>
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.result_summary') }}</p>
                                                            <p class="mt-1">{{ $this->selectedRecord->result_summary }}</p>
                                                        </div>
                                                    @endif
                                                    @if (filled($this->selectedRecord->impact_summary))
                                                        <div>
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.impact_summary') }}</p>
                                                            <p class="mt-1">{{ $this->selectedRecord->impact_summary }}</p>
                                                        </div>
                                                    @endif
                                                    @if (filled($this->selectedRecord->source_url))
                                                        <div>
                                                            <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.source_url') }}</p>
                                                            <p class="mt-2">
                                                                <a href="{{ $this->selectedRecord->source_url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-zinc-700 underline">
                                                                    {{ __('personnel::portfolio.actions.open_link') }}
                                                                </a>
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <x-professional-portfolio.attachment-row :attachment="$this->selectedRecord->certificateAttachment" :label="__('personnel::portfolio.fields.certificate')" />
                                        <x-professional-portfolio.attachment-row :attachment="$this->selectedRecord->agendaAttachment" :label="__('personnel::portfolio.fields.agenda')" />
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
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $editingId ? __('personnel::portfolio.actions.edit') : __('personnel::portfolio.actions.add_event') }}</h3>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="cancelForm" wire:target="cancelForm" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.cancel') }}</x-ui.async-button>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.event_type')" :error="$errors->first('form.event_type')">
                            <select wire:model.live="form.event_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::eventTypes() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.event_type.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.participation_role')" :error="$errors->first('form.participation_role')">
                            <select wire:model.live="form.participation_role" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::participationRoles() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.participation_role.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.title')" :error="$errors->first('form.title')">
                        <input wire:model.defer="form.title" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                    </x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.topic')" :error="$errors->first('form.topic')">
                            <input wire:model.defer="form.topic" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.organizer_name')" :error="$errors->first('form.organizer_name')">
                            <input wire:model.defer="form.organizer_name" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.start_date')" :error="$errors->first('form.start_date')">
                            <input wire:model.defer="form.start_date" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.end_date')" :error="$errors->first('form.end_date')">
                            <input wire:model.defer="form.end_date" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.location')" :error="$errors->first('form.location')">
                            <input wire:model.defer="form.location" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.country')" :error="$errors->first('form.country_id')">
                            <select wire:model.defer="form.country_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                <option value="">—</option>
                                @foreach ($this->countryOptions as $country)
                                    <option value="{{ $country['id'] }}">{{ $country['title'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.attendance_format')" :error="$errors->first('form.attendance_format')">
                            <select wire:model.defer="form.attendance_format" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::attendanceFormats() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.attendance_format.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.strategic_level')" :error="$errors->first('form.strategic_level')">
                        <select wire:model.defer="form.strategic_level" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                            @foreach ($options::strategicLevels() as $option)
                                <option value="{{ $option }}">{{ __('personnel::portfolio.options.strategic_level.'.$option) }}</option>
                            @endforeach
                        </select>
                    </x-ui.input-shell>
                    @if (($form['participation_role'] ?? null) === 'participant')
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.hr_value_reason')" :error="$errors->first('form.hr_value_reason')">
                            <textarea wire:model.defer="form.hr_value_reason" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea>
                        </x-ui.input-shell>
                    @endif
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.result_summary')" :error="$errors->first('form.result_summary')">
                        <textarea wire:model.defer="form.result_summary" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.impact_summary')" :error="$errors->first('form.impact_summary')">
                        <textarea wire:model.defer="form.impact_summary" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea>
                    </x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.source_url')" :error="$errors->first('form.source_url')">
                            <input wire:model.defer="form.source_url" type="url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.visibility')" :error="$errors->first('form.visibility')">
                            <select wire:model.defer="form.visibility" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::eventVisibilities() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.visibility.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.file-upload-shell wire:model="certificateUpload" :label="__('personnel::portfolio.fields.certificate')" :error="$errors->first('certificateUpload')" :upload="$certificateUpload" :existing-name="$editingId && $this->selectedRecord?->certificateAttachment ? $this->selectedRecord->certificateAttachment->original_name : null" />
                        <x-ui.file-upload-shell wire:model="agendaUpload" :label="__('personnel::portfolio.fields.agenda')" :error="$errors->first('agendaUpload')" :upload="$agendaUpload" :existing-name="$editingId && $this->selectedRecord?->agendaAttachment ? $this->selectedRecord->agendaAttachment->original_name : null" />
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.notes')" :error="$errors->first('form.notes')">
                        <textarea wire:model.defer="form.notes" rows="2" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea>
                    </x-ui.input-shell>
                </div>

                <div class="mt-4">
                    <x-ui.async-button variant="primary" full-width="true" wire:click="save" wire:target="save" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.save_record') }}</x-ui.async-button>
                </div>
            </div>
        @endif
    </div>
</div>
