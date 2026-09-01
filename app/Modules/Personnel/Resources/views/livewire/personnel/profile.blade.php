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
    <x-page-header
        :title="$personnel->fullname"
        :breadcrumb="__('personnel::common.labels.tabel').' № '.$personnel->tabel_no"
    >
        <x-slot:actions>
            @include('partials.personnel.profile-actions')
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-4 px-4 py-4 sm:px-5">
        {{-- ===================== identity ===================== --}}
        <section class="rounded-2xl border border-hairline bg-white p-4 shadow-card sm:p-5">
            <div class="flex items-start gap-4">
                <x-avatar :name="$personnel->fullname" :tone="$tone" size="lg" />

                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ $personnel->fullname }}</h2>
                        <x-small-badge :mode="$tone === 'neutral' ? 'secondary' : $tone" dot>
                            {{ $reader->statusLabel($personnel) }}
                        </x-small-badge>
                    </div>
                    <p class="text-[13px] text-ink-soft">{{ $personnel->position_label }}</p>
                    @if ($structurePath !== '')
                        <p class="text-[12px] text-ink-faint">{{ $structurePath }}</p>
                    @endif
                </div>
            </div>

            <dl class="mt-5 grid gap-x-6 gap-y-4 border-t border-hairline-subtle pt-4 sm:grid-cols-3 xl:grid-cols-6">
                @foreach ($reader->identityMeta($personnel) as $item)
                    <div class="min-w-0">
                        <dt class="hrm-eyebrow">{{ $item['label'] }}</dt>
                        <dd @class(['mt-1 truncate text-[13px] text-ink', 'hrm-num' => $item['mono']]) title="{{ $item['value'] }}">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

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
        @endif
    </div>

    @include('partials.personnel.profile-modals')
</div>
