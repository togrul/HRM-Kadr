@php
    use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
    use App\Modules\Personnel\Livewire\PersonnelProfile;

    $reader = app(PersonnelProfileReadService::class);
    $personnel = $this->personnel;

    $counts = $reader->sectionCounts($personnel);
    $tone = $reader->statusTone($personnel);
    $structurePath = $reader->structurePath($personnel);

    $steps = PersonnelProfile::SECTION_STEPS;
    $currentStep = $this->wizardStep();
    $editing = $this->wizardIsMounted();
@endphp

<div class="flex flex-col">
    {{-- ===================== step navigation ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('personnel::profile.title')"
            :subtitle="$personnel->fullname"
        >
            <x-context-panel.section :padded="true">
                <x-context-panel.item
                    wire:click.prevent="setSection('overview')"
                    wire:loading.attr="disabled"
                    wire:target="setSection"
                    :active="$section === 'overview'"
                >{{ __('personnel::profile.sections.overview') }}</x-context-panel.item>
            </x-context-panel.section>

            <x-context-panel.section :title="__('personnel::profile.groups.file')" :padded="true">
                @foreach ($steps as $key => $number)
                    @php
                        // Same completed / active / upcoming semantics as the wizard's stepper.
                        $state = match (true) {
                            ! $editing => 'upcoming',
                            $number < $currentStep => 'completed',
                            $number === $currentStep => 'active',
                            default => 'upcoming',
                        };
                    @endphp

                    <x-context-panel.step
                        wire:click.prevent="setSection('{{ $key }}')"
                        wire:loading.attr="disabled"
                        wire:target="setSection"
                        :number="$number"
                        :state="$state"
                        :count="$counts[$key] ?: null"
                        :disabled="! $this->canEdit"
                    >{{ __('personnel::profile.sections.'.$key) }}</x-context-panel.step>
                @endforeach
            </x-context-panel.section>

            <x-slot name="footer">
                <a href="{{ route('personnel.index') }}" wire:navigate class="inline-flex items-center gap-2 text-[12px] font-medium text-ink-muted transition hover:text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>{{ __('personnel::profile.actions.back_to_list') }}</span>
                </a>
            </x-slot>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    {{-- One flush block: breadcrumb, identity with the actions beside it, then the key facts.
         The name is shown once — the page title IS the identity. --}}
    <header class="border-b border-hairline bg-white px-4 pb-4 pt-3.5 sm:px-5">
        <nav class="flex min-w-0 flex-wrap items-center gap-x-1.5 text-[12.5px] text-ink-faint" aria-label="breadcrumb">
            <span>{{ __('ui::common.labels.breadcrumb_root') }}</span>
            <span class="text-ink-faint/70">/</span>
            <a href="{{ route('personnel.index') }}" wire:navigate class="transition hover:text-ink">{{ __('personnel::common.titles.personnels') }}</a>
            <span class="text-ink-faint/70">/</span>
            @if ($editing)
                <button type="button" wire:click="setSection('overview')" class="transition hover:text-ink">{{ __('personnel::common.labels.tabel') }} № <span class="hrm-num">{{ $personnel->tabel_no }}</span></button>
                <span class="text-ink-faint/70">/</span>
                <span class="font-medium text-ink-soft">{{ __('personnel::common.titles.edit_personnel') }}</span>
            @else
                <span class="text-ink-soft">{{ __('personnel::common.labels.tabel') }} № <span class="hrm-num">{{ $personnel->tabel_no }}</span></span>
            @endif
        </nav>

        <div class="mt-3.5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <x-avatar :name="$personnel->fullname" :tone="$tone" size="xl" />

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="truncate text-[21px] font-semibold tracking-[-0.03em] text-ink">{{ $personnel->fullname }}</h1>
                        <x-small-badge :mode="$tone === 'neutral' ? 'green' : $tone" dot>
                            {{ $reader->statusLabel($personnel) }}
                        </x-small-badge>
                    </div>
                    <p class="mt-0.5 truncate text-[13.5px] text-ink-muted" title="{{ $structurePath }}">
                        {{ $personnel->position_label }}@if ($structurePath !== '')<span class="px-1.5 text-ink-faint">·</span>{{ $structurePath }}@endif
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @include('partials.personnel.profile-actions')
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-hairline-subtle pt-4 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($reader->identityMeta($personnel) as $item)
                <div class="min-w-0">
                    <dt class="hrm-eyebrow">{{ $item['label'] }}</dt>
                    <dd @class(['mt-1.5 truncate text-[13.5px] text-ink', 'hrm-num' => $item['mono'], 'text-ink-faint' => $item['empty']]) title="{{ $item['value'] }}">{{ $item['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </header>

    <div class="space-y-4 px-4 py-4 sm:px-5">
        {{-- ===================== body ===================== --}}
        @if ($editing)
            {{-- One stable key: the wizard stays mounted across section changes so its
                 validate-and-save handshake still guards unsaved edits. --}}
            <div>
                <livewire:personnel.edit-personnel
                    :personnelModel="$personnel->id"
                    :step="$currentStep"
                    chromeless
                    :key="'personnel-file-wizard-'.$personnel->id"
                />
            </div>
        @else
            @include('personnel::livewire.personnel.profile-sections.overview', [
                'personnel' => $personnel,
                'reader' => $reader,
            ])

            {{-- what has happened to this person lately; the 360 read spans many tables, so the
                 list loads after first paint. The header and footer stay outside the island: an
                 action fired inside an island re-renders only the island, so the side panel would
                 open empty. The type filter targets the island, so it re-renders only the list. Rows are read-only. --}}
            <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-5 py-3">
                    <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::profile.recent.title') }}</h2>
                    <x-ui.select wire:model.live="recentType" wire:island="profile-recent-events" class="!w-40" aria-label="{{ __('personnel::portfolio.fields.timeline_type') }}">
                        <option value="">{{ __('personnel::common.labels.all') }}</option>
                        @foreach (\App\Modules\Personnel\Application\Services\Personnel360TimelineService::TYPES as $type)
                            <option value="{{ $type }}">{{ __('personnel::portfolio.timeline.'.$type) }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                @island(name: 'profile-recent-events', lazy: true)
                @placeholder
                    <div class="h-40 animate-pulse bg-[#fafafa]"></div>
                @endplaceholder
                @php
                    $events = $this->recentEvents;
                @endphp
                @if ($events === [])
                    <p class="px-5 py-8 text-center text-[13px] text-ink-faint">{{ __('personnel::profile.recent.empty') }}</p>
                @else
                    <ol class="divide-y divide-hairline-subtle">
                        @foreach ($events as $event)
                            <li class="grid grid-cols-[5.5rem_minmax(0,1fr)_auto] items-start gap-x-4 px-5 py-3.5 sm:grid-cols-[6.5rem_minmax(0,1fr)_auto]">
                                <span class="hrm-num pt-px text-[12.5px] text-ink-faint">{{ filled($event['occurred_at'] ?? null) ? \Illuminate\Support\Carbon::parse($event['occurred_at'])->format('d.m.Y') : '—' }}</span>

                                <div class="min-w-0">
                                    <p class="truncate text-[14px] font-semibold tracking-[-0.01em] text-ink">{{ $event['title'] }}</p>
                                    @if (! empty($event['changes']))
                                        <div class="mt-1.5 space-y-1.5">
                                            @foreach ($event['changes'] as $change)
                                                <div class="flex flex-wrap items-center gap-1.5 text-[12.5px]">
                                                    @if (count($event['changes']) > 1)
                                                        <span class="text-ink-faint">{{ $change['field'] }}:</span>
                                                    @endif
                                                    <span class="max-w-full truncate rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-ink-muted">{{ $change['old'] }}</span>
                                                    <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                                    <span class="max-w-full truncate rounded-md border border-hairline bg-white px-1.5 py-0.5 font-medium text-ink">{{ $change['new'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif (filled($event['summary'] ?? null))
                                        <p class="mt-0.5 truncate text-[12.5px] text-ink-faint">{{ $event['summary'] }}</p>
                                    @endif
                                </div>

                                <span class="hrm-badge bg-[#f4f4f5] text-ink-muted">{{ __('personnel::portfolio.timeline.'.$event['type']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @endisland

                @if ($this->canEdit)
                    <div class="border-t border-hairline-subtle px-5 py-3">
                        <button type="button" wire:click="openSideMenu('show-information', @js($this->personnel->tabel_no), 'employee-360')" class="inline-flex items-center gap-1 text-[13px] font-medium text-ink-soft transition hover:text-ink">
                            {{ __('personnel::profile.recent.view_all') }}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                @endif
            </section>
        @endif
    </div>

    @include('partials.personnel.profile-modals')
</div>
