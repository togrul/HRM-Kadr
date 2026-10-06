{{-- Live calculation summary, shown once metrics were auto-calculated or overridden. --}}
@if($autoCalculatedPreview || $manualMetricOverride)
    <div class="md:col-span-3">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-zinc-800">{{ __('attendance::manual_entries.labels.live_calculation_summary') }}</p>
                <p class="text-xs text-zinc-500">
                    @if($preview['baseline_source'] === 'explicit_shift')
                        {{ __('attendance::manual_entries.summaries.calculated_with_selected_shift', ['shift' => $preview['baseline_label']]) }}
                    @elseif($preview['baseline_source'] === 'assignment_shift')
                        {{ __('attendance::manual_entries.summaries.calculated_with_assigned_shift', ['shift' => $preview['baseline_label']]) }}
                    @elseif($preview['baseline_source'] === 'default_shift')
                        {{ __('attendance::manual_entries.summaries.calculated_with_default_shift', ['shift' => $preview['baseline_label']]) }}
                    @elseif($preview['baseline_source'] === 'manual_override')
                        {{ __('attendance::manual_entries.descriptions.manual_override_active') }}
                    @else
                        {{ __('attendance::manual_entries.descriptions.no_shift_baseline') }}
                    @endif
                </p>
            </div>

            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-zinc-500">
                <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('attendance::manual_entries.labels.source') }}: {{ $baselineSourceLabels[$preview['baseline_source']] ?? $preview['baseline_source'] }}</span>
                @if($preview['baseline_label'])
                    <span class="rounded-full bg-white px-2 py-1 shadow-sm">{{ __('attendance::manual_entries.labels.shift') }}: {{ $preview['baseline_label'] }}</span>
                @endif
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-5">
                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.planned') }}</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['planned_minutes'] }}</p>
                </div>
                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.worked') }}</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900">{{ $preview['worked_minutes'] }}</p>
                </div>
                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.late_minutes') }}</p>
                    <p class="mt-1 text-lg font-semibold text-amber-600">{{ $preview['late_minutes'] }}</p>
                </div>
                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.early_leave_minutes') }}</p>
                    <p class="mt-1 text-lg font-semibold text-rose-600">{{ $preview['early_leave_minutes'] }}</p>
                </div>
                <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">{{ __('attendance::manual_entries.labels.overtime_minutes') }}</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">{{ $preview['overtime_minutes'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endif
