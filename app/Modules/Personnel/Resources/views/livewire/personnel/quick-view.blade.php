@php
    use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;

    $reader = app(PersonnelProfileReadService::class);
    $personnel = $this->personnel;
    $tone = $reader->statusTone($personnel);
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-start gap-3.5 border-b border-hairline px-5 py-4">
        <x-avatar :name="$personnel->fullname" :tone="$tone" size="lg" />

        <div class="min-w-0 flex-1 space-y-1">
            <h2 class="truncate text-[16px] font-semibold tracking-[-0.02em] text-ink">{{ $personnel->fullname }}</h2>
            <p class="truncate text-[12.5px] text-ink-soft">{{ $personnel->position_label }}</p>
            <p class="hrm-num text-[11.5px] text-ink-faint">{{ __('personnel::common.labels.tabel') }} № {{ $personnel->tabel_no }}</p>
        </div>

        <x-small-badge :mode="$tone === 'neutral' ? 'secondary' : $tone" dot>
            {{ $reader->statusLabel($personnel) }}
        </x-small-badge>
    </div>

    <dl class="hrm-scroll min-h-0 flex-1 overflow-y-auto px-5 py-2">
        @foreach ($this->rows as $row)
            <div class="flex items-baseline justify-between gap-6 border-b border-hairline-subtle py-3 last:border-b-0">
                <dt class="shrink-0 text-[12.5px] text-ink-muted">{{ $row['label'] }}</dt>
                <dd @class(['min-w-0 truncate text-right text-[12.5px] font-medium text-ink', 'hrm-num' => $row['mono']]) title="{{ $row['value'] }}">{{ $row['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="flex items-center gap-2 border-t border-hairline px-5 py-4">
        {{-- A full navigation, not wire:navigate: this panel is teleported to <body>,
             outside the page component Livewire would swap. --}}
        <x-pill-button variant="primary" class="flex-1 justify-center" :href="route('personnel.show', $personnel->id)">
            {{ __('personnel::profile.actions.open_profile') }}
        </x-pill-button>

        @can('edit-personnels')
            {{-- Editing lives on the personnel file page, not in a second modal. --}}
            <x-pill-button variant="secondary" :href="route('personnel.show', ['personnel' => $personnel->id, 'section' => 'personal'])">
                {{ __('personnel::common.actions.edit') }}
            </x-pill-button>
        @endcan
    </div>
</div>
