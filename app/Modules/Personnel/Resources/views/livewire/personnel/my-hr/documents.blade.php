@php
    $payload = $this->payload;
    $metricDots = [
        'total' => 'bg-[#a1a1aa]',
        'pdf' => 'bg-[#e11d48]',
        'image' => 'bg-[#7c3aed]',
        'other' => 'bg-[#0284c7]',
    ];
    $metrics = collect(['total', 'pdf', 'image', 'other'])
        ->map(fn (string $metric): array => [
            'label' => __('personnel::my_hr.documents.summary.'.$metric),
            'value' => $payload['summary'][$metric],
            'dot' => $metricDots[$metric],
        ])
        ->all();
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <p class="hrm-eyebrow">{{ __('personnel::my_hr.documents.kicker') }}</p>
            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.documents.description') }}</p>
        </div>

        @include('personnel::livewire.personnel.my-hr.partials.metric-strip', ['metrics' => $metrics])
    </section>

    @if ($payload['documents'] === [])
        <x-ui.empty-state icon="icons.document-icon" :title="__('personnel::my_hr.documents.empty.title')" :message="__('personnel::my_hr.documents.empty.body')" />
    @else
        <section class="rounded-xl border border-hairline bg-white">
            <div class="divide-y divide-hairline-subtle">
                @foreach ($payload['documents'] as $document)
                    @php
                        $ext = strtolower((string) $document['extension']);
                        $isPdf = str_contains($ext, 'pdf');
                        $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'heic'], true);
                        $tileClasses = $isPdf
                            ? 'bg-[#ffe4e6] text-[#be123c]'
                            : ($isImage ? 'bg-[#ede9fe] text-[#6d28d9]' : 'bg-[#e0f2fe] text-[#0369a1]');
                    @endphp
                    <div wire:key="my-hr-document-{{ $document['id'] }}" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[11px] {{ $tileClasses }}">
                            <x-icons.document-icon size="w-4 h-4" color="text-current" hover="text-current" />
                        </span>

                        <div class="min-w-0 flex-1 leading-tight">
                            <h3 class="truncate text-[13px] font-medium text-ink">{{ $document['title'] }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11.5px] text-ink-faint">
                                <x-small-badge mode="secondary">{{ $document['extension'] }}</x-small-badge>
                                <span>{{ $document['category_label'] }}</span>
                                <span class="text-hairline">&bull;</span>
                                <span class="hrm-num">{{ $document['created_at'] }}</span>
                                <span class="text-hairline">&bull;</span>
                                <span class="hrm-num">{{ $document['size_label'] }}</span>
                            </div>
                        </div>

                        <x-pill-button wire:click="openDocument({{ $document['id'] }})" wire:loading.attr="disabled" wire:target="openDocument" class="shrink-0">
                            {{ __('personnel::my_hr.documents.actions.open') }}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7m0 0H8m9 0v9"/></svg>
                        </x-pill-button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
