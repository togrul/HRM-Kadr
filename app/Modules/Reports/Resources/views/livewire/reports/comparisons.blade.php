<div class="space-y-5 px-4 py-3 lg:px-5">
    <x-surface-card :title="__('reports::dashboard.comparisons.title')" icon="icons.pending-icon" class="rounded-[2rem] border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]" bodyClass="rounded-[1.6rem] border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)]" contentClass="p-5 lg:p-6">
        <div class="space-y-4">
            <p class="text-sm leading-7 text-zinc-500">{{ __('reports::dashboard.comparisons.description') }}</p>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.year') }}</label>
                    <select wire:model.live="year" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                        @foreach (range(now()->year - 4, now()->year + 1) as $yearOption)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.month') }}</label>
                    <select wire:model.live="month" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                        @foreach (range(1, 12) as $monthOption)
                            <option value="{{ $monthOption }}">{{ \Carbon\Carbon::create()->month($monthOption)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('reports::dashboard.fields.structure') }}</label>
                    <select wire:model.live="structureId" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                        <option value="">{{ __('reports::dashboard.labels.all_structures') }}</option>
                        @foreach ($structureOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </x-surface-card>

    @php
        $headcountMax = max(1, (int) collect($this->payload['headcount_years'])->max('value'));
        $performanceMax = max(1, (int) collect($this->payload['performance_distribution'])->max('value'));
    @endphp

    <div class="grid gap-5 xl:grid-cols-2">
        <x-surface-card :title="__('reports::dashboard.comparisons.cards.headcount_yoy')" icon="icons.profile-icon">
            <div class="space-y-4">
                @foreach ($this->payload['headcount_years'] as $row)
                    @php
                        $width = max(12, min(100, round(($row['value'] / $headcountMax) * 100, 1)));
                    @endphp
                    <div class="rounded-3xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                        <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-zinc-800">{{ $row['label'] }}</span>
                            <span class="text-zinc-500">{{ $row['value'] }}</span>
                        </div>
                        <div class="h-[18px] rounded-md border border-zinc-100 bg-zinc-50 px-[6px] py-[5px]">
                            <div
                                class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_8px,transparent_8px_12px)]"
                                style="width: {{ $width }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-surface-card>

        <x-surface-card :title="__('reports::dashboard.comparisons.cards.attendance_mom')" icon="icons.calendar-icon">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($this->payload['attendance_months'] as $row)
                    <div class="rounded-3xl border border-zinc-200/90 bg-zinc-50/70 px-5 py-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)]">
                        <p class="text-sm font-semibold text-zinc-900">{{ $row['label'] }}</p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('reports::dashboard.overview.cards.attendance_coverage') }}</p>
                                <p class="mt-2 text-3xl font-semibold text-zinc-950">{{ number_format($row['coverage_pct'], 1) }}%</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('reports::dashboard.overview.cards.absence_rate') }}</p>
                                <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ number_format($row['absence_rate_pct'], 1) }}%</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <x-surface-card :title="__('reports::dashboard.comparisons.cards.training_yoy')" icon="icons.training-icon">
            <div class="space-y-3">
                @forelse ($this->payload['training_years'] as $row)
                    <div class="rounded-3xl border border-zinc-200/90 bg-zinc-50/70 px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-zinc-900">{{ $row['label'] }}</p>
                            <x-small-badge mode="sky">{{ $row['sessions_count'] }}</x-small-badge>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('reports::dashboard.fields.attended_hours') }}: {{ $row['attended_hours'] }}</p>
                    </div>
                @empty
                    @include('reports::components.report-placeholder', [
                        'title' => __('reports::dashboard.comparisons.cards.training_yoy'),
                        'message' => __('reports::dashboard.empty.no_report_data'),
                        'compact' => true,
                    ])
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('reports::dashboard.comparisons.cards.performance_distribution')" icon="icons.performance-icon">
            <div class="space-y-4">
                @forelse ($this->payload['performance_distribution'] as $row)
                    @php
                        $width = max(12, min(100, round(($row['value'] / $performanceMax) * 100, 1)));
                    @endphp
                    <div class="rounded-3xl border border-zinc-200/90 bg-zinc-50/70 px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)]">
                        <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-zinc-800">{{ $row['label'] }}</span>
                            <span class="text-zinc-500">{{ $row['value'] }}</span>
                        </div>
                        <div class="h-[18px] rounded-md border border-zinc-100 bg-zinc-50 px-[6px] py-[5px]">
                            <div
                                class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_8px,transparent_8px_12px)]"
                                style="width: {{ $width }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    @include('reports::components.report-placeholder', [
                        'title' => __('reports::dashboard.comparisons.cards.performance_distribution'),
                        'message' => __('reports::dashboard.empty.no_report_data'),
                        'compact' => true,
                    ])
                @endforelse
            </div>
        </x-surface-card>
    </div>
</div>
