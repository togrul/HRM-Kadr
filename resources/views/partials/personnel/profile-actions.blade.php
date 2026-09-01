@php
    $personnel = $this->personnel;

    // Everything the row's icon strip used to hide, grouped and given real labels.
    // Icons carry over from the row's old shortcut strip, so the same action reads the
    // same way wherever it is offered.
    $menu = collect([
        ['menu' => 'show-files', 'value' => $personnel->tabel_no, 'icon' => 'icons.files-icon', 'label' => __('personnel::common.actions.files'), 'can' => $this->canEdit],
        ['menu' => 'show-information', 'value' => $personnel->tabel_no, 'icon' => 'icons.profile-outline-icon', 'label' => __('personnel::common.actions.information'), 'can' => $this->canEdit],
        ['menu' => 'show-vacations', 'value' => $personnel->tabel_no, 'icon' => 'icons.vacation-outline-icon', 'label' => __('personnel::common.actions.vacations'), 'can' => $this->canEdit],
        ['menu' => 'professional-portfolio', 'value' => $personnel->id, 'icon' => 'icons.briefcase-outline-icon', 'label' => __('personnel::common.actions.professional_portfolio'), 'can' => $this->canViewProfessionalPortfolio()],
        ['menu' => 'my-hr-account', 'value' => $personnel->id, 'icon' => 'icons.my-hr-icon', 'label' => __('personnel::common.actions.self_service_account'), 'can' => $this->canManageMyHrAccounts()],
        ['menu' => 'onboarding-documents', 'value' => $personnel->id, 'icon' => 'icons.onboarding-library-icon', 'label' => __('personnel::common.actions.onboarding_documents'), 'can' => $this->canManageOnboardingDocuments()],
        ['menu' => 'learning-materials', 'value' => $personnel->id, 'icon' => 'icons.learning-library-icon', 'label' => __('personnel::common.actions.learning_materials'), 'can' => $this->canManageLearningMaterials()],
    ])->filter(fn (array $item): bool => (bool) $item['can'])->values();

    $links = [
        ['href' => route('print.personnel', $personnel->id), 'icon' => 'icons.print-outline-icon', 'label' => __('personnel::common.actions.print')],
        ['href' => route('print.cv', $personnel->id), 'icon' => 'icons.cv-outline', 'label' => __('personnel::profile.actions.print_cv')],
        ['href' => route('print.cv.word', $personnel->id), 'icon' => 'icons.download-icon', 'label' => __('personnel::profile.actions.export_word')],
    ];
@endphp

<x-pill-button
    variant="secondary"
    :href="route('personnel.index')"
    wire:navigate
    class="shrink-0"
>
    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    <span>{{ __('personnel::profile.actions.back_to_list') }}</span>
</x-pill-button>

@if ($menu->isNotEmpty() || filled($links))
    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
        <x-pill-button variant="secondary" @click="open = ! open" :aria-expanded="false" x-bind:aria-expanded="open.toString()">
            <span>{{ __('personnel::profile.actions.more') }}</span>
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-bind:class="open ? 'rotate-180' : ''"><path d="m6 9 6 6 6-6"/></svg>
        </x-pill-button>

        <div
            x-cloak
            x-show="open"
            x-transition.opacity.duration.100ms
            @click.outside="open = false"
            class="absolute right-0 z-40 mt-1.5 w-60 overflow-hidden rounded-xl border border-hairline bg-white py-1 shadow-overlay"
        >
            @foreach ($menu as $item)
                <button
                    type="button"
                    @click="open = false"
                    wire:click="openSideMenu('{{ $item['menu'] }}', '{{ $item['value'] }}')"
                    wire:loading.attr="disabled"
                    wire:target="openSideMenu"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                >
                    <span class="hrm-icon flex h-4 w-4 shrink-0 items-center justify-center text-ink-faint">
                        <x-dynamic-component :component="$item['icon']" size="w-4 h-4" color="text-current" hover="text-current" />
                    </span>
                    <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                </button>
            @endforeach

            @if ($menu->isNotEmpty())
                <span class="my-1 block h-px bg-hairline-subtle"></span>
            @endif

            @foreach ($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    target="_blank"
                    rel="noopener"
                    @click="open = false"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                >
                    <span class="hrm-icon flex h-4 w-4 shrink-0 items-center justify-center text-ink-faint">
                        <x-dynamic-component :component="$link['icon']" size="w-4 h-4" color="text-current" hover="text-current" />
                    </span>
                    <span class="min-w-0 truncate">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

@if ($this->canEdit && $section === 'overview')
    <x-pill-button variant="primary" wire:click="setSection('personal')" wire:loading.attr="disabled" wire:target="setSection">
        {{ __('personnel::common.actions.edit') }}
    </x-pill-button>
@endif
