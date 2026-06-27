@php
    $payload = $this->payload;
    $metricAccents = [
        'total' => 'bg-zinc-400',
        'pdf' => 'bg-rose-500',
        'image' => 'bg-violet-500',
        'other' => 'bg-sky-500',
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.documents.kicker') }}</x-ui.field-label>
            <h2 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.documents.title') }}</h2>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.documents.description') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach (['total', 'pdf', 'image', 'other'] as $metric)
                <div class="group rounded-2xl border border-zinc-200 bg-white px-5 py-4 shadow-sm transition hover:border-zinc-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('personnel::my_hr.documents.summary.'.$metric) }}</span>
                        <span class="h-2 w-2 rounded-full {{ $metricAccents[$metric] }}"></span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $payload['summary'][$metric] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @if ($payload['documents'] === [])
        <x-ui.empty-state icon="icons.document-icon" :title="__('personnel::my_hr.documents.empty.title')" :message="__('personnel::my_hr.documents.empty.body')" class="py-14" />
    @else
        <div class="space-y-3">
            @foreach ($payload['documents'] as $document)
                @php
                    $ext = strtolower((string) $document['extension']);
                    $isPdf = str_contains($ext, 'pdf');
                    $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'heic'], true);
                    $tileClasses = $isPdf
                        ? 'bg-rose-50 text-rose-600'
                        : ($isImage ? 'bg-violet-50 text-violet-600' : 'bg-sky-50 text-sky-600');
                @endphp
                <div class="flex flex-col gap-4 rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-zinc-300 hover:shadow-md sm:flex-row sm:items-center">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $tileClasses }}">
                        <x-icons.document-icon size="w-6 h-6" color="text-current" hover="text-current" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-[15px] font-semibold tracking-tight text-zinc-950">{{ $document['title'] }}</h3>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs font-medium text-zinc-500">
                            <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-600">{{ $document['extension'] }}</span>
                            <span>{{ $document['category_label'] }}</span>
                            <span class="text-zinc-300">&bull;</span>
                            <span>{{ $document['created_at'] }}</span>
                            <span class="text-zinc-300">&bull;</span>
                            <span>{{ $document['size_label'] }}</span>
                        </div>
                    </div>

                    <button type="button" wire:click="openDocument({{ $document['id'] }})" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-zinc-950 px-4 py-2.5 text-sm font-semibold tracking-tight text-white shadow-sm transition hover:bg-zinc-800">
                        {{ __('personnel::my_hr.documents.actions.open') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17 17 7m0 0H8m9 0v9" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
