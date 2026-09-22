@php
    $d = 'performance_evaluation::dashboard';
    $resolutionSourceLabel = static function (?string $source): string {
        return match ($source) {
            'manual' => __('performance_evaluation::dashboard.resolution_sources.manual'),
            'self_service_provisioned' => __('performance_evaluation::dashboard.resolution_sources.self_service_provisioned'),
            'manual_self_service_link' => __('performance_evaluation::dashboard.resolution_sources.manual_self_service_link'),
            null, '' => '—',
            default => __('performance_evaluation::dashboard.resolution_sources.unknown'),
        };
    };
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $iconButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300';
    $stats = [
        ['total_links', $this->linkStats['total'], 'bg-sky-500'],
        ['manual_links', $this->linkStats['manual'], 'bg-emerald-500'],
        ['links_resolved_today', $this->linkStats['resolved_today'], 'bg-amber-500'],
    ];
@endphp

<div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-pill-button :href="$this->backUrl">
            <span aria-hidden="true">←</span>
            <span>{{ __($d.'.actions.back_to_performance_dashboard') }}</span>
        </x-pill-button>
        <p class="text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ __($d.'.cards.user_personnel_links') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($stats as [$label, $value, $dot])
            <div class="rounded-2xl border border-hairline bg-white px-4 py-3 shadow-card">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                    <p class="hrm-eyebrow truncate">{{ __($d.'.labels.'.$label) }}</p>
                </div>
                <p class="hrm-num mt-1.5 text-[22px] font-semibold leading-none tracking-[-0.03em] text-ink">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
        {{-- editor --}}
        <div class="self-start overflow-visible rounded-2xl border border-hairline bg-white shadow-card">
            <div class="{{ $sectionHead }} rounded-t-2xl"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.user_personnel_link_editor') }}</p></div>
            <div class="flex flex-col gap-4 p-5">
                <p class="rounded-xl bg-[#fafafa] px-3.5 py-2.5 text-[12px] leading-5 text-ink-muted">{{ __($d.'.hints.user_personnel_links') }}</p>

                <div>
                    <x-ui.select-dropdown
                        :label="__($d.'.fields.user')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="perf-user-personnel-link-user"
                        wire:model.live="linkForm.user_id"
                        :model="$this->userOptions()"
                        search-model="searchLinkedUser"
                    ></x-ui.select-dropdown>
                    @error('linkForm.user_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div>
                    <x-ui.select-dropdown
                        :label="__($d.'.fields.personnel')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="perf-user-personnel-link-personnel"
                        wire:model.live="linkForm.personnel_id"
                        :model="$this->personnelOptions()"
                        search-model="searchLinkedPersonnel"
                    ></x-ui.select-dropdown>
                    @error('linkForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>

                <div class="flex justify-end border-t border-hairline-subtle pt-4">
                    <button type="button" wire:click="saveLink" class="h-10 rounded-xl bg-ink px-4 text-[13px] font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_user_personnel_link') }}</button>
                </div>
            </div>
        </div>

        {{-- current links --}}
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.current_user_personnel_links') }}</p>
            </div>
            <div class="border-b border-hairline-subtle px-5 py-3">
                <div class="flex h-10 items-center gap-2 rounded-xl border border-hairline bg-[#fafafa] px-3 focus-within:border-zinc-400 focus-within:bg-white">
                    <svg class="h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <label for="user-personnel-link-search" class="sr-only">{{ __($d.'.fields.search') }}</label>
                    <input id="user-personnel-link-search" type="text" wire:model.live.debounce.300ms="searchLinks" placeholder="{{ __($d.'.fields.search') }}"
                        class="h-full w-full border-0 bg-transparent px-0 text-[13px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0">
                </div>
            </div>

            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->links as $link)
                    <div wire:key="user-personnel-link-{{ $link->id }}" class="group flex flex-col gap-3 px-5 py-3 transition-colors hover:bg-[#fafafa] md:flex-row md:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <x-avatar size="sm" :name="$link->user_name ?: '—'" />
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-semibold text-ink">{{ $link->user_name ?: '—' }}</p>
                                <p class="truncate text-[11.5px] text-ink-faint">{{ $link->user_email ?: '—' }}</p>
                            </div>
                        </div>
                        <svg class="hidden h-4 w-4 shrink-0 text-ink-faint md:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] text-ink-soft">{{ $link->personnel_fullname ?: '—' }}</p>
                            <p class="hrm-num text-[11.5px] text-ink-faint">#{{ $link->personnel_tabel_no ?: '—' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                            <span class="rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted" title="{{ __($d.'.fields.resolution_source') }}">{{ $resolutionSourceLabel($link->resolution_source) }}</span>
                            <span class="hrm-num rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted">{{ optional($link->resolved_at)->format('d.m.Y H:i') ?: '—' }}</span>
                            <div class="flex items-center gap-0.5 md:opacity-0 md:transition-opacity md:group-hover:opacity-100 md:focus-within:opacity-100">
                                <button type="button" wire:click="editLink({{ $link->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </button>
                                <button type="button" wire:click="requestDeleteLink({{ $link->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                    <x-icons.delete-icon size="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.user_personnel_links') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-ui.delete-confirmation-modal />
</div>
