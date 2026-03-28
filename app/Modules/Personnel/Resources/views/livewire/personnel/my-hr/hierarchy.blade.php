@php($payload = $this->payload)

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.kicker') }}</x-ui.field-label>
            <h2 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.hierarchy.title') }}</h2>
            <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.hierarchy.description') }}</p>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.hierarchy.summary.manager') }}</x-ui.field-label>
                <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $payload['summary']['manager']['fullname'] }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.hierarchy.summary.structure') }}</x-ui.field-label>
                <p class="mt-2 text-base font-semibold tracking-tight text-zinc-950">{{ $payload['summary']['structure'] }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.hierarchy.summary.chain_count') }}</x-ui.field-label>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $payload['summary']['chain_count'] }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr.hierarchy.summary.direct_reports') }}</x-ui.field-label>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $payload['summary']['direct_reports_count'] }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-4">
            <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.current_profile') }}</x-ui.field-label>
                <div class="mt-4 rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ $payload['self']['fullname'] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600">{{ $payload['self']['position'] }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ $payload['self']['structure'] }}</p>
                </div>
            </div>

            <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.direct_reports') }}</x-ui.field-label>
                @if ($payload['direct_reports'] === [])
                    <p class="mt-4 text-sm text-zinc-500">{{ __('personnel::my_hr.hierarchy.empty.direct_reports') }}</p>
                @else
                    <div class="mt-4 grid gap-3">
                        @foreach ($payload['direct_reports'] as $row)
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                                <p class="text-base font-semibold tracking-tight text-zinc-950">{{ $row['fullname'] }}</p>
                                <p class="mt-2 text-sm text-zinc-600">{{ $row['position'] }}</p>
                                <p class="mt-1 text-sm text-zinc-500">{{ $row['structure'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.approval_line') }}</x-ui.field-label>
                <div class="mt-4 grid gap-3">
                    @foreach ($payload['approval_routes'] as $route)
                        <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
                                    {{ __('personnel::my_hr.hierarchy.request_types.'.$route['type']) }}
                                </span>
                                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700">
                                    {{ __('personnel::my_hr.hierarchy.route_sources.'.$route['source']) }}
                                </span>
                                <span @class([
                                    'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $route['hr_always_included'],
                                    'border-zinc-200 bg-white text-zinc-600' => ! $route['hr_always_included'],
                                ])>
                                    {{ $route['hr_always_included'] ? __('personnel::my_hr.hierarchy.labels.hr_active') : __('personnel::my_hr.hierarchy.labels.hr_inactive') }}
                                </span>
                            </div>

                            <p class="mt-4 text-sm leading-6 text-zinc-600">
                                @if (! $route['primary_enabled'])
                                    {{ __('personnel::my_hr.hierarchy.messages.hr_only_help') }}
                                @elseif ($route['upper_enabled'])
                                    {{ __('personnel::my_hr.hierarchy.messages.upper_policy_help') }}
                                @else
                                    {{ __('personnel::my_hr.hierarchy.messages.primary_policy_help') }}
                                @endif
                            </p>

                            <div class="mt-4 grid gap-3 xl:grid-cols-3">
                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.primary_step') }}</x-ui.field-label>
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ $route['approver']['fullname'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">{{ $route['approver']['position'] }}</p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.upper_step') }}</x-ui.field-label>
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">{{ $route['fallback_approver']['fullname'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">{{ $route['fallback_approver']['id'] ? $route['fallback_approver']['position'] : __('personnel::my_hr.hierarchy.empty.fallback') }}</p>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.hr_step') }}</x-ui.field-label>
                                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-950">
                                        {{ $route['hr_always_included'] ? __('personnel::my_hr.hierarchy.labels.hr_active') : __('personnel::my_hr.hierarchy.labels.hr_inactive') }}
                                    </p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-600">
                                        {{ $route['hr_always_included'] ? __('personnel::my_hr.hierarchy.messages.hr_policy_help') : __('personnel::my_hr.hierarchy.messages.hr_policy_inactive_help') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.hierarchy.labels.reporting_line') }}</x-ui.field-label>
            @if ($payload['manager_chain'] === [])
                <p class="mt-4 text-sm text-zinc-500">{{ __('personnel::my_hr.hierarchy.empty.manager') }}</p>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($payload['manager_chain'] as $index => $row)
                        <div class="flex gap-4">
                            <div class="relative flex w-8 justify-center">
                                @if (! $loop->last)
                                    <div class="absolute left-1/2 top-6 h-[calc(100%+0.75rem)] w-px -translate-x-1/2 bg-zinc-200"></div>
                                @endif
                                <div class="mt-1 h-3 w-3 rounded-full bg-zinc-950 ring-4 ring-zinc-100"></div>
                            </div>
                            <div class="flex-1 rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-base font-semibold tracking-tight text-zinc-950">{{ $row['fullname'] }}</p>
                                        <p class="mt-2 text-sm text-zinc-600">{{ $row['position'] }}</p>
                                        <p class="mt-1 text-sm text-zinc-500">{{ $row['structure'] }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-tight text-zinc-600">
                                        {{ $index === 0 ? __('personnel::my_hr.hierarchy.labels.direct_manager') : __('personnel::my_hr.hierarchy.labels.upper_line') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
