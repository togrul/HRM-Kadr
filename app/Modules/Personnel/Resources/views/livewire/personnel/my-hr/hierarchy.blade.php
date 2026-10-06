@php
    $payload = $this->payload;
@endphp

<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <p class="hrm-eyebrow">{{ __('personnel::my_hr.hierarchy.kicker') }}</p>
            <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.hierarchy.description') }}</p>
        </div>

        {{-- 12 columns so the structure path gets the room it needs to be read in full,
             while the two plain numbers stay narrow. --}}
        <div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-12">
            <x-fact-tile
                :label="__('personnel::my_hr.hierarchy.summary.manager')"
                :value="$payload['summary']['manager']['fullname']"
                class="xl:col-span-3"
            />
            <x-fact-tile :label="__('personnel::my_hr.hierarchy.summary.structure')" class="sm:col-span-2 xl:col-span-5">
                <span class="block leading-relaxed">{{ $payload['summary']['structure'] }}</span>
            </x-fact-tile>
            <x-fact-tile
                :label="__('personnel::my_hr.hierarchy.summary.chain_count')"
                :value="$payload['summary']['chain_count']"
                class="xl:col-span-2"
            />
            <x-fact-tile
                :label="__('personnel::my_hr.hierarchy.summary.direct_reports')"
                :value="$payload['summary']['direct_reports_count']"
                class="xl:col-span-2"
            />
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="flex flex-col gap-4">
            <section class="rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <p class="hrm-eyebrow">{{ __('personnel::my_hr.hierarchy.labels.current_profile') }}</p>
                </div>
                <div class="flex items-center gap-3 px-4 py-3">
                    <x-avatar :name="$payload['self']['fullname']" />
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $payload['self']['fullname'] }}</p>
                        <p class="mt-0.5 truncate text-[12px] text-ink-muted">{{ $payload['self']['position'] }}</p>
                        <p class="truncate text-[11.5px] text-ink-faint">{{ $payload['self']['structure'] }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <p class="hrm-eyebrow">{{ __('personnel::my_hr.hierarchy.labels.direct_reports') }}</p>
                </div>

                @if ($payload['direct_reports'] === [])
                    <p class="px-4 py-3 text-[12.5px] text-ink-faint">{{ __('personnel::my_hr.hierarchy.empty.direct_reports') }}</p>
                @else
                    <div class="divide-y divide-hairline-subtle">
                        @foreach ($payload['direct_reports'] as $row)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <x-avatar :name="$row['fullname']" size="sm" />
                                <div class="min-w-0 leading-tight">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $row['fullname'] }}</p>
                                    <p class="truncate text-[11.5px] text-ink-faint">{{ $row['position'] }} · {{ $row['structure'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <p class="hrm-eyebrow">{{ __('personnel::my_hr.hierarchy.labels.approval_line') }}</p>
                </div>

                <div class="space-y-3 p-3">
                    @foreach ($payload['approval_routes'] as $route)
                        <div wire:key="my-hr-route-{{ $route['type'] }}" class="rounded-xl border border-hairline bg-[#fafafa] p-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-small-badge mode="blue">{{ __('personnel::my_hr.hierarchy.request_types.'.$route['type']) }}</x-small-badge>
                                <x-small-badge mode="secondary">{{ __('personnel::my_hr.hierarchy.route_sources.'.$route['source']) }}</x-small-badge>
                                <x-small-badge :mode="$route['hr_always_included'] ? 'green' : 'secondary'" dot>
                                    {{ $route['hr_always_included'] ? __('personnel::my_hr.hierarchy.labels.hr_active') : __('personnel::my_hr.hierarchy.labels.hr_inactive') }}
                                </x-small-badge>
                            </div>

                            <p class="mt-2 text-[12.5px] leading-relaxed text-ink-muted">
                                @if (! $route['primary_enabled'])
                                    {{ __('personnel::my_hr.hierarchy.messages.hr_only_help') }}
                                @elseif ($route['upper_enabled'])
                                    {{ __('personnel::my_hr.hierarchy.messages.upper_policy_help') }}
                                @else
                                    {{ __('personnel::my_hr.hierarchy.messages.primary_policy_help') }}
                                @endif
                            </p>

                            <div class="mt-3 grid gap-3 xl:grid-cols-3">
                                <x-fact-tile
                                    :label="__('personnel::my_hr.hierarchy.labels.primary_step')"
                                    :value="$route['approver']['fullname']"
                                    :note="$route['approver']['position']"
                                    class="bg-white"
                                />
                                <x-fact-tile
                                    :label="__('personnel::my_hr.hierarchy.labels.upper_step')"
                                    :value="$route['fallback_approver']['fullname']"
                                    :note="$route['fallback_approver']['id'] ? $route['fallback_approver']['position'] : __('personnel::my_hr.hierarchy.empty.fallback')"
                                    class="bg-white"
                                />
                                <x-fact-tile
                                    :label="__('personnel::my_hr.hierarchy.labels.hr_step')"
                                    :value="$route['hr_always_included'] ? __('personnel::my_hr.hierarchy.labels.hr_active') : __('personnel::my_hr.hierarchy.labels.hr_inactive')"
                                    :note="$route['hr_always_included'] ? __('personnel::my_hr.hierarchy.messages.hr_policy_help') : __('personnel::my_hr.hierarchy.messages.hr_policy_inactive_help')"
                                    class="bg-white"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <p class="hrm-eyebrow">{{ __('personnel::my_hr.hierarchy.labels.reporting_line') }}</p>
            </div>

            @if ($payload['manager_chain'] === [])
                <p class="px-4 py-3 text-[12.5px] text-ink-faint">{{ __('personnel::my_hr.hierarchy.empty.manager') }}</p>
            @else
                <div class="space-y-3 p-3">
                    @foreach ($payload['manager_chain'] as $index => $row)
                        <div class="flex gap-3">
                            <div class="relative flex w-4 justify-center">
                                @if (! $loop->last)
                                    <div class="absolute left-1/2 top-4 h-[calc(100%+0.75rem)] w-px -translate-x-1/2 bg-hairline"></div>
                                @endif
                                <div class="mt-1.5 h-2 w-2 rounded-full bg-ink ring-4 ring-[#f4f4f5]"></div>
                            </div>

                            <div class="flex-1 rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 leading-tight">
                                        <p class="truncate text-[13px] font-semibold text-ink">{{ $row['fullname'] }}</p>
                                        <p class="mt-0.5 truncate text-[12px] text-ink-muted">{{ $row['position'] }}</p>
                                        <p class="truncate text-[11.5px] text-ink-faint">{{ $row['structure'] }}</p>
                                    </div>
                                    <x-small-badge mode="secondary">
                                        {{ $index === 0 ? __('personnel::my_hr.hierarchy.labels.direct_manager') : __('personnel::my_hr.hierarchy.labels.upper_line') }}
                                    </x-small-badge>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
