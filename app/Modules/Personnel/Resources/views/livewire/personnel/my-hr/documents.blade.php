@php
    $payload = $this->payload;
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
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.documents.summary.'.$metric) }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $payload['summary'][$metric] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @if ($payload['documents'] === [])
        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.documents.empty.title') }}</h3>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.documents.empty.body') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($payload['documents'] as $document)
                <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                <h3 class="text-base font-semibold tracking-tight text-zinc-950">{{ $document['title'] }}</h3>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700">{{ $document['extension'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700">{{ $document['category_label'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700">{{ $document['created_at'] }}</span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700">{{ $document['size_label'] }}</span>
                            </div>
                        </div>

                        <button type="button" wire:click="openDocument({{ $document['id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800">
                            {{ __('personnel::my_hr.documents.actions.open') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
