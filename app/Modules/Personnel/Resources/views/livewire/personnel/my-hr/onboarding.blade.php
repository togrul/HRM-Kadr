@php
    $payload = $this->payload;
    $summary = $payload['summary'];
    $rows = $payload['rows'];

    $metrics = [
        ['label' => __('personnel::my_hr.onboarding.summary.total'), 'value' => $summary['total'], 'dot' => 'bg-[#a1a1aa]'],
        ['label' => __('personnel::my_hr.onboarding.summary.pending'), 'value' => $summary['pending'], 'dot' => 'bg-[#f59e0b]'],
        ['label' => __('personnel::my_hr.onboarding.summary.acknowledged'), 'value' => $summary['acknowledged'], 'dot' => 'bg-[#059669]'],
        ['label' => __('personnel::my_hr.onboarding.summary.required'), 'value' => $summary['required'], 'dot' => 'bg-[#0284c7]'],
    ];

    $statusTone = fn (string $mode): string => match ($mode) {
        'emerald' => 'green',
        'rose' => 'rose',
        'sky' => 'blue',
        default => 'secondary',
    };
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <p class="hrm-eyebrow">{{ __('personnel::my_hr.onboarding.kicker') }}</p>
            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.onboarding.description') }}</p>
        </div>

        @include('personnel::livewire.personnel.my-hr.partials.metric-strip', ['metrics' => $metrics])
    </section>

    @forelse ($rows as $row)
        <section wire:key="my-hr-onboarding-{{ $row['id'] }}" class="rounded-xl border border-hairline bg-white">
            <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h3 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $row['title'] }}</h3>
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <x-small-badge :mode="$statusTone($row['status_mode'])" dot>{{ $row['status_label'] }}</x-small-badge>
                        <x-small-badge mode="secondary">{{ $row['document_type_label'] }}</x-small-badge>
                        <x-small-badge mode="secondary">{{ __('personnel::my_hr.onboarding.labels.version') }}: {{ $row['version'] }}</x-small-badge>
                        @if ($row['is_required'])
                            <x-small-badge mode="amber">{{ __('personnel::my_hr.onboarding.labels.required') }}</x-small-badge>
                        @endif
                        @if ($row['requires_acknowledgement'])
                            <x-small-badge mode="secondary">{{ __('personnel::my_hr.onboarding.labels.requires_acknowledgement') }}</x-small-badge>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-pill-button wire:click="openDocument({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="openDocument({{ $row['id'] }})">
                        {{ __('personnel::my_hr.onboarding.actions.open_document') }}
                    </x-pill-button>
                    @if ($row['can_acknowledge'])
                        <x-pill-button variant="primary" wire:click="acknowledge({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="acknowledge({{ $row['id'] }})">
                            {{ __('personnel::my_hr.onboarding.actions.acknowledge') }}
                        </x-pill-button>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-fact-tile :label="__('personnel::my_hr.onboarding.labels.assigned_at')" :value="$row['assigned_at']" />
                <x-fact-tile :label="__('personnel::my_hr.onboarding.labels.due_at')" :value="$row['due_at'] ?: '—'" />
                <x-fact-tile :label="__('personnel::my_hr.onboarding.labels.opened_at')" :value="$row['opened_at'] ?: '—'" />
                <x-fact-tile :label="__('personnel::my_hr.onboarding.labels.acknowledged_at')" :value="$row['acknowledged_at'] ?: '—'" />
            </div>
        </section>
    @empty
        <x-ui.empty-state icon="icons.document-icon" :title="__('personnel::my_hr.onboarding.empty.title')" :message="__('personnel::my_hr.onboarding.empty.body')" />
    @endforelse
</div>
