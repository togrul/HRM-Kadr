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
                        @foreach ($options::mediaStatuses() as $status)
                            <option value="{{ $status }}">{{ __('personnel::portfolio.status.'.$status) }}</option>
                        @endforeach
                    </select>
                </x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_from')" labelClass="tracking-tight text-zinc-500">
                  <input wire:model.live="dateFrom" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" /></x-ui.input-shell>
                <x-ui.input-shell :label="__('personnel::portfolio.fields.date_to')" labelClass="tracking-tight text-zinc-500"><input wire:model.live="dateTo" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" /></x-ui.input-shell>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">{{ __('personnel::portfolio.tabs.media') }}</p>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportExcel" wire:target="exportExcel" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_excel') }}</x-ui.async-button>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="exportCsv" wire:target="exportCsv" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_csv') }}</x-ui.async-button>
                    @can('manage-personnel-media-records')
                        <x-ui.async-button variant="primary" wire:click="openCreate" wire:target="openCreate" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.add_media') }}</x-ui.async-button>
                    @endcan
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->records as $record)
                    <div class="rounded-[24px] border {{ $record->verification_status === 'pending' ? 'border-amber-200 bg-amber-50/40 shadow-sm shadow-amber-100/40' : 'border-zinc-200 bg-white' }} p-4">
                        <div class="space-y-3">
                            <div class="rounded-[22px] border border-zinc-200 bg-zinc-50/80 px-4 py-3.5">
                                <h3 class="max-w-[38rem] text-lg font-semibold tracking-tight text-zinc-950">{{ $record->headline }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-notification.chip mode="{{ $record->verification_status === 'verified' ? 'emerald' : ($record->verification_status === 'rejected' ? 'rose' : ($record->verification_status === 'broken_link' ? 'amber' : 'muted')) }}">{{ __('personnel::portfolio.status.'.$record->verification_status) }}</x-notification.chip>
                                <x-notification.chip mode="sky">{{ $record->publisher_name }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ optional($record->published_at)->format('d.m.Y H:i') }}</x-notification.chip>
                                <x-notification.chip mode="muted">{{ __('personnel::portfolio.options.mention_type.'.$record->mention_type) }}</x-notification.chip>
                            </div>

                            <div class="border-t border-zinc-100"></div>

                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                <x-ui.async-button variant="secondary" size="sm" fullWidth="true" wire:click="selectRecord({{ $record->id }})" wire:target="selectRecord({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.show_details') }}</x-ui.async-button>
                                @can('manage-personnel-media-records')
                                    <x-ui.async-button variant="primary" size="sm" fullWidth="true" wire:click="edit({{ $record->id }})" wire:target="edit({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.edit') }}</x-ui.async-button>
                                @endcan
                                @canany(['verify-professional-portfolio-records', 'verify-personnel-media-records'])
                                    @if ($record->verification_status !== 'broken_link')
                                        <x-ui.async-button variant="warning" size="sm" fullWidth="true" wire:click="markBrokenLink({{ $record->id }})" wire:target="markBrokenLink({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.mark_broken_link') }}</x-ui.async-button>
                                    @endif
                                @endcanany
                            </div>

                            @canany(['verify-professional-portfolio-records', 'verify-personnel-media-records'])
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @if ($record->verification_status !== 'verified')
                                        <x-ui.async-button variant="success" size="sm" fullWidth="true" wire:click="verify({{ $record->id }})" wire:target="verify({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.verify') }}</x-ui.async-button>
                                    @endif
                                    @if ($record->verification_status !== 'archived_only')
                                        <x-ui.async-button variant="secondary" size="sm" fullWidth="true" wire:click="markArchivedOnly({{ $record->id }})" wire:target="markArchivedOnly({{ $record->id }})" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.mark_archived_only') }}</x-ui.async-button>
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
                                                <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.summary') }}</p>
                                                <p class="mt-1 text-sm leading-6 text-zinc-700">{{ $this->selectedRecord->summary }}</p>
                                            </div>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.sentiment') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ __('personnel::portfolio.options.sentiment.'.$this->selectedRecord->sentiment) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.visibility') }}</p>
                                                    <p class="mt-1 text-base font-semibold tracking-tight text-zinc-900">{{ __('personnel::portfolio.options.visibility.'.$this->selectedRecord->visibility) }}</p>
                                                </div>
                                            </div>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.link_health') }}</p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                                        @php
                                                            $linkMode = match ($this->selectedRecord->link_check_status) {
                                                                'ok' => 'emerald',
                                                                'broken' => 'amber',
                                                                default => 'muted',
                                                            };
                                                        @endphp
                                                        <x-notification.chip mode="{{ $linkMode }}">{{ $this->selectedRecord->link_check_status ? __('personnel::portfolio.health.link.'.$this->selectedRecord->link_check_status) : '—' }}</x-notification.chip>
                                                        @if ($this->selectedRecord->link_checked_at)
                                                            <span class="text-xs text-zinc-500">{{ $this->selectedRecord->link_checked_at->format('d.m.Y H:i') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.archive_health') }}</p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                                        @php
                                                            $archiveMode = ($this->selectedRecord->archive_health_status ?? null) === 'ok' ? 'emerald' : (($this->selectedRecord->archive_health_status ?? null) === 'missing' ? 'rose' : 'muted');
                                                        @endphp
                                                        <x-notification.chip mode="{{ $archiveMode }}">{{ $this->selectedRecord->archive_health_status ? __('personnel::portfolio.health.archive.'.$this->selectedRecord->archive_health_status) : '—' }}</x-notification.chip>
                                                        @if ($this->selectedRecord->archive_checked_at)
                                                            <span class="text-xs text-zinc-500">{{ $this->selectedRecord->archive_checked_at->format('d.m.Y H:i') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <x-professional-portfolio.attachment-row :attachment="$this->selectedRecord->archiveAttachment" :label="__('personnel::portfolio.fields.archive')" />
                                        <x-professional-portfolio.attachment-row :attachment="$this->selectedRecord->screenshotAttachment" :label="__('personnel::portfolio.fields.screenshot')" />
                                    </div>

                                    @if ($this->selectedRecord->url)
                                        <div class="rounded-[24px] border border-zinc-200 bg-white p-4">
                                            <a href="{{ $this->selectedRecord->url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-zinc-700 underline">{{ __('personnel::portfolio.actions.open_link') }}</a>
                                        </div>
                                    @endif
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
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $editingId ? __('personnel::portfolio.actions.edit') : __('personnel::portfolio.actions.add_media') }}</h3>
                    <x-ui.async-button variant="secondary" size="sm" wire:click="cancelForm" wire:target="cancelForm" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.cancel') }}</x-ui.async-button>
                </div>
                <div class="mt-4 space-y-3">
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.headline')" :error="$errors->first('form.headline')"><input wire:model.defer="form.headline" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.publisher_name')" :error="$errors->first('form.publisher_name')"><input wire:model.defer="form.publisher_name" type="text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.published_at')" :error="$errors->first('form.published_at')"><input wire:model.defer="form.published_at" type="datetime-local" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.publisher_type')">
                            <select wire:model.defer="form.publisher_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::mediaPublisherTypes() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.publisher_type.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.mention_type')">
                            <select wire:model.defer="form.mention_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::mediaMentionTypes() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.mention_type.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.sentiment')">
                            <select wire:model.defer="form.sentiment" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::mediaSentiments() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.sentiment.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('personnel::portfolio.fields.visibility')">
                            <select wire:model.defer="form.visibility" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                                @foreach ($options::mediaVisibilities() as $option)
                                    <option value="{{ $option }}">{{ __('personnel::portfolio.options.visibility.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.url')" :error="$errors->first('form.url')"><input wire:model.defer="form.url" type="url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm" /></x-ui.input-shell>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.summary')" :error="$errors->first('form.summary')"><textarea wire:model.defer="form.summary" rows="4" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.file-upload-shell wire:model="archiveUpload" :label="__('personnel::portfolio.fields.archive')" :error="$errors->first('archiveUpload')" :upload="$archiveUpload" :existing-name="$editingId && $this->selectedRecord?->archiveAttachment ? $this->selectedRecord->archiveAttachment->original_name : null" />
                        <x-ui.file-upload-shell wire:model="screenshotUpload" :label="__('personnel::portfolio.fields.screenshot')" :error="$errors->first('screenshotUpload')" :upload="$screenshotUpload" :existing-name="$editingId && $this->selectedRecord?->screenshotAttachment ? $this->selectedRecord->screenshotAttachment->original_name : null" />
                    </div>
                    <x-ui.input-shell :label="__('personnel::portfolio.fields.notes')" :error="$errors->first('form.notes')"><textarea wire:model.defer="form.notes" rows="2" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm"></textarea></x-ui.input-shell>
                </div>
                <div class="mt-4">
                    <x-ui.async-button variant="primary" fullWidth="true" wire:click="save" wire:target="save" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.save_record') }}</x-ui.async-button>
                </div>
            </div>
        @endif
    </div>
</div>
