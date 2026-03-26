@php
    $resolutionSourceLabel = static function (?string $source): string {
        return match ($source) {
            'manual' => __('performance_evaluation::dashboard.resolution_sources.manual'),
            'self_service_provisioned' => __('performance_evaluation::dashboard.resolution_sources.self_service_provisioned'),
            'manual_self_service_link' => __('performance_evaluation::dashboard.resolution_sources.manual_self_service_link'),
            null, '' => '—',
            default => __('performance_evaluation::dashboard.resolution_sources.unknown'),
        };
    };
@endphp

<div class="flex flex-col space-y-4 px-6 py-4">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $this->backUrl }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50">
            <span aria-hidden="true">←</span>
            <span>{{ __('performance_evaluation::dashboard.actions.back_to_performance_dashboard') }}</span>
        </a>
    </div>

    <x-surface-card :title="__('performance_evaluation::dashboard.cards.user_personnel_links')" icon="icons.profile-icon">
        <div class="space-y-5">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::dashboard.labels.total_links') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->linkStats['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('performance_evaluation::dashboard.labels.manual_links') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->linkStats['manual'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::dashboard.labels.links_resolved_today') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->linkStats['resolved_today'] }}</p>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.user_personnel_link_editor')" icon="icons.edit-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs leading-6 text-zinc-600">
                            {{ __('performance_evaluation::dashboard.hints.user_personnel_links') }}
                        </div>

                        <x-ui.select-dropdown
                            :label="__('performance_evaluation::dashboard.fields.user')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            instance="perf-user-personnel-link-user"
                            wire:model.live="linkForm.user_id"
                            :model="$this->userOptions()"
                            search-model="searchLinkedUser"
                        ></x-ui.select-dropdown>
                        @error('linkForm.user_id') <x-validation>{{ $message }}</x-validation> @enderror

                        <x-ui.select-dropdown
                            :label="__('performance_evaluation::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            instance="perf-user-personnel-link-personnel"
                            wire:model.live="linkForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchLinkedPersonnel"
                        ></x-ui.select-dropdown>
                        @error('linkForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror

                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="saveLink">{{ __('performance_evaluation::dashboard.actions.save_user_personnel_link') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.current_user_personnel_links')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="space-y-4">
                        <div>
                            <x-label for="user-personnel-link-search">{{ __('performance_evaluation::dashboard.fields.search') }}</x-label>
                            <x-livewire-input mode="gray" id="user-personnel-link-search" wire:model.live.debounce.300ms="searchLinks" />
                        </div>

                        @forelse ($this->links as $link)
                            <x-ui.list-card>
                                <div class="space-y-3">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-zinc-900">{{ $link->user_name ?: '—' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $link->user_email ?: '—' }}</p>
                                        <p class="text-sm text-zinc-700">{{ $link->personnel_fullname ?: '—' }}</p>
                                        <p class="text-xs text-zinc-500">#{{ $link->personnel_tabel_no ?: '—' }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.resolution_source') }}: {{ $resolutionSourceLabel($link->resolution_source) }}</x-small-badge>
                                        <x-small-badge mode="sky">{{ optional($link->resolved_at)->format('d.m.Y H:i') ?: '—' }}</x-small-badge>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.action-pill wire:click="editLink({{ $link->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                                        <x-ui.action-pill wire:click="requestDeleteLink({{ $link->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                                    </div>
                                </div>
                            </x-ui.list-card>
                        @empty
                            <x-ui.empty-state icon="icons.profile-icon" :message="__('performance_evaluation::dashboard.empty.user_personnel_links')" />
                        @endforelse
                    </div>
                </x-surface-card>
            </div>
        </div>
    </x-surface-card>

    <x-ui.delete-confirmation-modal />
</div>
