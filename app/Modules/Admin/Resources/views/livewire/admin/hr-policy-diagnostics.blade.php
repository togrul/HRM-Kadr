@php
    $enabledModules = collect($diagnostics['modules'])->where('enabled', true)->count();
    $enabledFeatures = collect($diagnostics['features'])->where('enabled', true)->count();
    $visibleMenus = collect($diagnostics['menu_visibility'])->where('visible', true)->count();
    $enabledPermissions = collect($diagnostics['permission_flags'])->where('enabled', true)->count();
@endphp

<div class="flex flex-col gap-5">
    <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-gradient-to-br from-white via-sky-50/40 to-emerald-50/40 shadow-sm">
        <div class="grid gap-5 px-6 py-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] lg:px-8 lg:py-8">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/80 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ __('admin::references.diagnostics.active_configuration') }}
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.kicker') }}</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 sm:text-4xl">{{ __('admin::references.menu.hr_policy_diagnostics') }}</h1>
                    <p class="max-w-3xl text-base leading-8 text-zinc-600">{{ __('admin::references.diagnostics.description') }}</p>
                    <p class="max-w-3xl text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.configuration_summary') }}</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div class="rounded-[1.75rem] border border-sky-200 bg-white/85 p-5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">{{ __('admin::references.diagnostics.active_profile') }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $diagnostics['active_profile'] }}</p>
                </div>
                <div class="rounded-[1.75rem] border border-emerald-200 bg-white/85 p-5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-700">{{ __('admin::references.diagnostics.active_pack') }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $diagnostics['pack_label'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 2xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)]">
        <section class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm lg:p-7">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.pack_summary') }}</p>
                    <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ $diagnostics['pack_label'] }}</h2>
                    <p class="text-sm leading-7 text-zinc-600">{{ $diagnostics['pack_description'] }}</p>
                    <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.recommended_for') }}: {{ $diagnostics['recommended_for'] ?: '—' }}</p>
                </div>
                <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                    {{ __('admin::references.diagnostics.active') }}
                </div>
            </div>

            <div class="mt-6 rounded-[1.75rem] border border-zinc-200 bg-zinc-50/80 p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold tracking-tight text-zinc-950">{{ __('admin::references.diagnostics.modules_and_permissions') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-zinc-500">{{ __('admin::references.diagnostics.pack_cards_help') }}</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['label' => __('admin::references.diagnostics.enabled_modules'), 'value' => $enabledModules, 'tone' => 'sky'],
                        ['label' => __('admin::references.diagnostics.enabled_features'), 'value' => $enabledFeatures, 'tone' => 'violet'],
                        ['label' => __('admin::references.diagnostics.visible_menus'), 'value' => $visibleMenus, 'tone' => 'amber'],
                        ['label' => __('admin::references.diagnostics.enabled_permissions'), 'value' => $enabledPermissions, 'tone' => 'emerald'],
                    ] as $item)
                        <div @class([
                            'rounded-[1.4rem] border px-4 py-4 shadow-sm',
                            'border-sky-200 bg-sky-50/70' => $item['tone'] === 'sky',
                            'border-violet-200 bg-violet-50/70' => $item['tone'] === 'violet',
                            'border-amber-200 bg-amber-50/70' => $item['tone'] === 'amber',
                            'border-emerald-200 bg-emerald-50/70' => $item['tone'] === 'emerald',
                        ])>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500">{{ $item['label'] }}</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm lg:p-7">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.available_packs') }}</p>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('admin::references.diagnostics.available_packs') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.pack_cards_help') }}</p>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($availablePacks as $pack)
                    <div @class([
                        'rounded-[1.6rem] border px-4 py-4 shadow-sm transition-colors',
                        'border-emerald-200 bg-emerald-50/60' => $pack['key'] === $diagnostics['active_pack'],
                        'border-zinc-200 bg-zinc-50/70' => $pack['key'] !== $diagnostics['active_pack'],
                    ])>
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-lg font-semibold tracking-tight text-zinc-950">{{ $pack['label'] }}</p>
                                <p class="text-sm leading-7 text-zinc-600">{{ $pack['description'] }}</p>
                            </div>
                            @if ($pack['key'] === $diagnostics['active_pack'])
                                <span class="inline-flex shrink-0 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ __('admin::references.diagnostics.active') }}</span>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/90 bg-white/90 px-3 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-zinc-400">{{ __('admin::references.diagnostics.recommended_for') }}</p>
                                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $pack['recommended_for'] ?: '—' }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/90 bg-white/90 px-3 py-3">
                                <div class="flex items-center justify-between text-sm text-zinc-600">
                                    <span>{{ __('admin::references.diagnostics.visible_menus') }}</span>
                                    <span class="font-semibold text-zinc-950">{{ $pack['menu_count'] }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-sm text-zinc-600">
                                    <span>{{ __('admin::references.diagnostics.enabled_permissions') }}</span>
                                    <span class="font-semibold text-zinc-950">{{ $pack['permission_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm lg:p-7">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.workflow_defaults') }}</p>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('admin::references.diagnostics.workflow_defaults') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.workflow_defaults_help') }}</p>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($diagnostics['workflow_defaults'] as $settings)
                    <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-1">
                                <p class="text-base font-semibold tracking-tight text-zinc-950">{{ $settings['label'] }}</p>
                                <p class="text-sm text-zinc-500">{{ __('admin::references.diagnostics.workflow_defaults_help') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (($settings['tabs'] ?? []) as $tab)
                                    <span class="inline-flex rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium uppercase tracking-tight text-zinc-600">{{ $tab }}</span>
                                @endforeach
                                @foreach (($settings['test_tabs'] ?? []) as $tab)
                                    <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium uppercase tracking-tight text-sky-700">{{ $tab }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm lg:p-7">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.approval_defaults') }}</p>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('admin::references.diagnostics.approval_defaults') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.approval_defaults_help') }}</p>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($diagnostics['self_service_approval'] as $requestType => $settings)
                    <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4 shadow-sm">
                        <p class="text-base font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.requests.types.'.$requestType) }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ([
                                ['label' => __('admin::references.fields.include_primary_approver'), 'value' => (bool) ($settings['include_primary_approver'] ?? false)],
                                ['label' => __('admin::references.fields.include_upper_approver'), 'value' => (bool) ($settings['include_upper_approver'] ?? false)],
                                ['label' => __('admin::references.fields.hr_always_included'), 'value' => (bool) ($settings['hr_always_included'] ?? false)],
                            ] as $row)
                                <span @class([
                                    'inline-flex rounded-full border px-3 py-1 text-xs font-medium uppercase tracking-tight',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $row['value'],
                                    'border-zinc-200 bg-white text-zinc-500' => ! $row['value'],
                                ])>{{ $row['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm lg:p-7">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('admin::references.diagnostics.stored_overrides') }}</p>
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('admin::references.diagnostics.stored_overrides') }}</h2>
            <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.stored_overrides_help') }}</p>
        </div>

        @if ($approvalOverrides->isEmpty())
            <div class="mt-5 rounded-[1.6rem] border border-dashed border-zinc-300 bg-zinc-50/70 px-5 py-10 text-center text-sm leading-7 text-zinc-500">
                Bu deployment üçün ayrıca saxlanmış təsdiq siyasəti yoxdur.
            </div>
        @else
            <div class="mt-5 grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                @foreach ($approvalOverrides as $route)
                    <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-base font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.requests.types.'.$route->request_type) }}</p>
                                <p class="mt-1 text-sm text-zinc-500">Bu müraciət növü üçün ayrıca saxlanmış fərdi qayda.</p>
                            </div>
                            <span @class([
                                'inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight',
                                'border-emerald-200 bg-emerald-50 text-emerald-700' => (bool) $route->is_active,
                                'border-zinc-200 bg-white text-zinc-500' => ! (bool) $route->is_active,
                            ])>{{ (bool) $route->is_active ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no') }}</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @foreach ([
                                ['label' => __('admin::references.fields.include_primary_approver'), 'value' => (bool) $route->include_primary_approver],
                                ['label' => __('admin::references.fields.include_upper_approver'), 'value' => (bool) $route->include_upper_approver],
                                ['label' => __('admin::references.fields.hr_always_included'), 'value' => (bool) $route->hr_always_included],
                                ['label' => __('admin::references.fields.is_active'), 'value' => (bool) $route->is_active],
                            ] as $row)
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-white px-3 py-2.5">
                                    <span class="text-xs font-medium text-zinc-600">{{ $row['label'] }}</span>
                                    <span @class([
                                        'inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $row['value'],
                                        'border-zinc-200 bg-zinc-50 text-zinc-500' => ! $row['value'],
                                    ])>{{ $row['value'] ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
