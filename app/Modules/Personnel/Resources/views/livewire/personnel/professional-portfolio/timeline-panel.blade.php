<div class="space-y-5">
    <x-ui.filter-panel inner-class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1.2fr)_minmax(12rem,.85fr)_minmax(10rem,.7fr)_minmax(10rem,.7fr)]">
        <x-ui.filter-field :label="__('personnel::portfolio.fields.search')">
            <x-ui.filter-input wire:model.live.debounce.300ms="search" placeholder="{{ __('personnel::portfolio.messages.search_placeholder') }}" />
        </x-ui.filter-field>
        <x-ui.filter-field :label="__('personnel::portfolio.fields.timeline_type')">
            <x-ui.filter-native-select wire:model.live="type">
                <option value="">{{ __('personnel::portfolio.timeline.all') }}</option>
                @foreach ($this->typeOptions() as $option)
                    <option value="{{ $option }}">{{ __('personnel::portfolio.timeline.'.$option) }}</option>
                @endforeach
            </x-ui.filter-native-select>
        </x-ui.filter-field>
        <x-ui.filter-field :label="__('personnel::portfolio.fields.date_from')">
            <x-ui.filter-input wire:model.live="dateFrom" type="date" />
        </x-ui.filter-field>
        <x-ui.filter-field :label="__('personnel::portfolio.fields.date_to')">
            <x-ui.filter-input wire:model.live="dateTo" type="date" />
        </x-ui.filter-field>
        <div class="flex items-end md:col-span-2 xl:col-span-4 xl:justify-start">
            <x-ui.filter-reset-button wire:click="resetFilters" :label="__('personnel::portfolio.actions.reset_filters')">
                {{ __('personnel::common.actions.reset') }}
            </x-ui.filter-reset-button>
        </div>
    </x-ui.filter-panel>

    <x-surface-card :title="$panelTitle ?? __('personnel::portfolio.tabs.timeline')" content-class="p-0" clip>
        <div class="divide-y divide-zinc-100">
        @forelse ($this->timelineItems as $item)
            @php
                $typeMode = match ($item['type']) {
                    'event' => 'sky',
                    'media' => 'amber',
                    'project' => 'emerald',
                    'order' => 'sky',
                    'leave', 'vacation', 'business_trip' => 'amber',
                    'training_need', 'training_delivery' => 'emerald',
                    'performance' => 'rose',
                    'audit' => 'zinc',
                    default => 'muted',
                };
                $statusMode = match ($item['status']) {
                    'verified' => 'emerald',
                    'created' => 'emerald',
                    'rejected' => 'rose',
                    'deleted', 'force_deleted' => 'rose',
                    'broken_link', 'archived_only' => 'amber',
                    'updated' => 'amber',
                    'restored' => 'sky',
                    default => 'muted',
                };
                $statusTranslationKey = filled($item['status']) ? 'personnel::portfolio.status.'.$item['status'] : null;
                $statusLabel = $statusTranslationKey ? __($statusTranslationKey) : null;
                if ($statusLabel === $statusTranslationKey) {
                    $statusLabel = $item['status'];
                }
                $markerClasses = match ($item['type']) {
                    'event' => 'border-sky-200 bg-sky-50 text-sky-700',
                    'media' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'project' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'order' => 'border-sky-200 bg-sky-50 text-sky-700',
                    'leave', 'vacation', 'business_trip' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'training_need', 'training_delivery' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'performance' => 'border-rose-200 bg-rose-50 text-rose-700',
                    'audit' => 'border-zinc-300 bg-zinc-100 text-zinc-700',
                    default => 'border-zinc-200 bg-zinc-50 text-zinc-500',
                };
            @endphp
            <article class="group grid gap-4 px-4 py-4 transition-colors hover:bg-zinc-50/60 md:grid-cols-[9rem_2rem_minmax(0,1fr)] md:px-5">
                <div class="flex items-start md:justify-end">
                    <div class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm font-semibold tracking-tight text-zinc-500">
                        {{ $item['occurred_at'] }}
                    </div>
                </div>

                <div class="relative hidden justify-center md:flex">
                    <div class="absolute bottom-[-1rem] top-[-1rem] w-px bg-zinc-200 group-first:top-3 group-last:bottom-3"></div>
                    <div class="relative mt-1 flex h-6 w-6 items-center justify-center rounded-full border bg-white shadow-sm {{ $markerClasses }}">
                        <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="rounded-[22px] border border-zinc-200 bg-white px-4 py-4 shadow-[0_1px_2px_rgba(16,24,40,0.035)]">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold leading-snug tracking-tight text-zinc-950">{{ $item['title'] }}</h3>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <x-notification.chip mode="{{ $typeMode }}">{{ __('personnel::portfolio.timeline.'.$item['type']) }}</x-notification.chip>
                                @if (filled($item['status']))
                                    <x-notification.chip mode="{{ $statusMode }}">{{ $statusLabel }}</x-notification.chip>
                                @endif
                            </div>
                        </div>

                        @if (filled($item['summary']))
                            <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $item['summary'] }}</p>
                        @endif

                        @if (filled($item['role']))
                            <div class="mt-3">
                                <x-notification.chip mode="muted">{{ $item['role'] }}</x-notification.chip>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="p-4">
                <x-ui.empty-state :title="__('personnel::portfolio.messages.empty')" />
            </div>
        @endforelse
        </div>
    </x-surface-card>
</div>
