<div class="space-y-5">
    <div class="grid gap-1 md:grid-cols-[minmax(0,1fr)_auto]">
        <x-ui.input-shell :label="__('personnel::portfolio.fields.search')" labelClass="tracking-tight text-zinc-500">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('personnel::portfolio.messages.search_placeholder') }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" />
        </x-ui.input-shell>
    </div>

    <div class="relative space-y-4">
        <div class="pointer-events-none absolute bottom-0 left-3 top-1 hidden w-px bg-zinc-200 md:block"></div>
        @forelse ($this->timelineItems as $item)
            @php
                $typeMode = match ($item['type']) {
                    'event' => 'sky',
                    'media' => 'amber',
                    'project' => 'emerald',
                    default => 'muted',
                };
                $statusMode = match ($item['status']) {
                    'verified' => 'emerald',
                    'rejected' => 'rose',
                    'broken_link', 'archived_only' => 'amber',
                    default => 'muted',
                };
                $markerClasses = match ($item['type']) {
                    'event' => 'border-sky-200 bg-sky-50 text-sky-700',
                    'media' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'project' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    default => 'border-zinc-200 bg-zinc-50 text-zinc-500',
                };
            @endphp
            <div class="relative md:pl-8">
                <div class="absolute left-3 top-1/2 hidden -translate-x-1/2 -translate-y-1/2 md:flex">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full border {{ $markerClasses }}">
                        <div class="h-2.5 w-2.5 rounded-full bg-current"></div>
                    </div>
                </div>

                <div class="rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-3">
                                <div class="rounded-[22px] border border-zinc-200 bg-zinc-50/80 px-4 py-3.5">
                                    <h3 class="max-w-[42rem] text-base font-semibold tracking-tight text-zinc-950">{{ $item['title'] }}</h3>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-notification.chip mode="{{ $typeMode }}">{{ __('personnel::portfolio.timeline.'.$item['type']) }}</x-notification.chip>
                                    @if (filled($item['role']))
                                        <x-notification.chip mode="muted">{{ $item['role'] }}</x-notification.chip>
                                    @endif
                                    @if (filled($item['status']))
                                        <x-notification.chip mode="{{ $statusMode }}">{{ __('personnel::portfolio.status.'.$item['status']) }}</x-notification.chip>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0">
                                <div class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-semibold tracking-tight text-zinc-500">
                                    {{ $item['occurred_at'] }}
                                </div>
                            </div>
                        </div>

                        @if (filled($item['summary']))
                            <div class="rounded-[20px] border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                                <p class="text-sm leading-6 text-zinc-700">{{ $item['summary'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-5 py-8 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
        @endforelse
    </div>
</div>
