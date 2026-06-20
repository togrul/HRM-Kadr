@php
    $modules = collect($diagnostics['modules']);
    $features = collect($diagnostics['features']);
    $menus = collect($diagnostics['menu_visibility']);
    $perms = collect($diagnostics['permission_flags']);

    $enabledModules = $modules->where('enabled', true)->count();
    $enabledFeatures = $features->where('enabled', true)->count();
    $visibleMenus = $menus->where('visible', true)->count();
    $enabledPermissions = $perms->where('enabled', true)->count();

    $totalModules = max($modules->count(), 0);
    $totalFeatures = max($features->count(), 0);
    $totalMenus = max($menus->count(), 0);
    $totalPermissions = max($perms->count(), 0);

    $totalAll = $totalModules + $totalFeatures + $totalMenus + $totalPermissions;
    $enabledAll = $enabledModules + $enabledFeatures + $visibleMenus + $enabledPermissions;
    $overallPct = $totalAll > 0 ? (int) round($enabledAll / $totalAll * 100) : 0;

    $kpis = [
        [
            'label' => __('admin::references.diagnostics.enabled_modules'),
            'value' => $enabledModules, 'total' => $totalModules, 'accent' => 'sky',
            'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        ],
        [
            'label' => __('admin::references.diagnostics.enabled_features'),
            'value' => $enabledFeatures, 'total' => $totalFeatures, 'accent' => 'violet',
            'icon' => '<path d="m12 3 1.9 4.3L18 9l-4.1 1.7L12 15l-1.9-4.3L6 9l4.1-1.7L12 3Z"/><path d="M19 14l.8 1.8L22 17l-2.2.9L19 20l-.8-1.9L16 17l2.2-1.2L19 14Z"/>',
        ],
        [
            'label' => __('admin::references.diagnostics.visible_menus'),
            'value' => $visibleMenus, 'total' => $totalMenus, 'accent' => 'amber',
            'icon' => '<rect x="3" y="4" width="18" height="4" rx="1.5"/><rect x="3" y="10" width="18" height="4" rx="1.5"/><rect x="3" y="16" width="11" height="4" rx="1.5"/>',
        ],
        [
            'label' => __('admin::references.diagnostics.enabled_permissions'),
            'value' => $enabledPermissions, 'total' => $totalPermissions, 'accent' => 'emerald',
            'icon' => '<path d="M12 3 5 6v5c0 4.5 3 7.6 7 9 4-1.4 7-4.5 7-9V6l-7-3Z"/><path d="m9.2 12 1.9 1.9 3.7-3.8"/>',
        ],
    ];

    $accentMap = [
        'sky' => 'bg-sky-50 text-sky-600 ring-sky-100',
        'violet' => 'bg-violet-50 text-violet-600 ring-violet-100',
        'amber' => 'bg-amber-50 text-amber-600 ring-amber-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    ];
    $barMap = [
        'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-500',
    ];
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-6">

    {{-- ───────────────────────── Hero ───────────────────────── --}}
    <section class="relative overflow-hidden rounded-[28px] bg-white ring-1 ring-zinc-200/70 shadow-[0_2px_8px_-2px_rgba(15,23,42,0.06)]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-br from-emerald-100/60 via-sky-100/40 to-transparent blur-2xl"></div>

        <div class="relative grid gap-8 p-7 sm:p-9 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="space-y-4">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-100">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    </span>
                    {{ __('admin::references.diagnostics.active_configuration') }}
                </span>

                <div class="space-y-3">
                    <p class="text-[13px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.kicker') }}</p>
                    <h1 class="text-[28px] font-semibold leading-tight tracking-[-0.02em] text-zinc-900 sm:text-[34px]">{{ __('admin::references.menu.hr_policy_diagnostics') }}</h1>
                    <p class="max-w-2xl text-[15px] leading-7 text-zinc-500">{{ __('admin::references.diagnostics.description') }}</p>
                </div>

                <div class="flex flex-wrap gap-2.5 pt-1">
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-zinc-50 px-4 py-2.5 ring-1 ring-inset ring-zinc-200/80">
                        <span class="text-[11px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.active_profile') }}</span>
                        <span class="text-sm font-semibold tracking-tight text-zinc-900">{{ $diagnostics['active_profile'] }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50/70 px-4 py-2.5 ring-1 ring-inset ring-emerald-100">
                        <span class="text-[11px] font-medium text-emerald-600/80">{{ __('admin::references.diagnostics.active_pack') }}</span>
                        <span class="text-sm font-semibold tracking-tight text-emerald-800">{{ $diagnostics['pack_label'] }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <a href="{{ route('admin.self-service-approval-routes') }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-xl bg-zinc-900 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        {{ __('admin::references.diagnostics.manage_overrides') }}
                    </a>
                    <p class="inline-flex items-center gap-1.5 text-[12px] leading-5 text-zinc-400">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                        <span class="max-w-md">{{ __('admin::references.diagnostics.config_readonly_note') }}</span>
                    </p>
                </div>
            </div>

            {{-- Overall enablement ring --}}
            <div class="flex items-center justify-center lg:pl-4">
                <div class="relative grid h-40 w-40 place-items-center rounded-full"
                     style="background: conic-gradient(#10b981 {{ $overallPct * 3.6 }}deg, #eef0f2 {{ $overallPct * 3.6 }}deg);">
                    <div class="grid h-[7.6rem] w-[7.6rem] place-items-center rounded-full bg-white shadow-[inset_0_1px_2px_rgba(0,0,0,0.04)]">
                        <span class="text-3xl font-semibold tracking-tight text-zinc-900 tabular-nums">{{ $overallPct }}<span class="text-lg text-zinc-400">%</span></span>
                        <span class="mt-0.5 text-[11px] font-medium text-zinc-400">{{ $enabledAll }} / {{ $totalAll }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── KPI tiles ───────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            @php $pct = $kpi['total'] > 0 ? (int) round($kpi['value'] / $kpi['total'] * 100) : 0; @endphp
            <div class="group rounded-3xl bg-white p-5 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_-8px_rgba(15,23,42,0.12)]">
                <div class="flex items-center justify-between">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl ring-1 ring-inset {{ $accentMap[$kpi['accent']] }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $kpi['icon'] !!}</svg>
                    </span>
                    <span class="text-[11px] font-semibold text-zinc-400 tabular-nums">{{ $pct }}%</span>
                </div>
                <p class="mt-4 text-[13px] font-medium text-zinc-500">{{ $kpi['label'] }}</p>
                <p class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-[28px] font-semibold leading-none tracking-tight text-zinc-900 tabular-nums">{{ $kpi['value'] }}</span>
                    <span class="text-sm font-medium text-zinc-400 tabular-nums">/ {{ $kpi['total'] }}</span>
                </p>
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100">
                    <div class="h-full rounded-full {{ $barMap[$kpi['accent']] }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ───────────────────────── Active pack + Available packs ───────────────────────── --}}
    <div class="grid gap-5 xl:grid-cols-[1fr_1fr]">
        {{-- Active pack --}}
        <section class="rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] lg:p-7">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1.5">
                    <p class="text-[12px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.pack_summary') }}</p>
                    <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ $diagnostics['pack_label'] }}</h2>
                    <p class="max-w-md text-sm leading-7 text-zinc-500">{{ $diagnostics['pack_description'] }}</p>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ __('admin::references.diagnostics.active') }}
                </span>
            </div>

            <div class="mt-5 rounded-2xl bg-zinc-50/80 p-4 ring-1 ring-inset ring-zinc-200/60">
                <p class="text-[11px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.recommended_for') }}</p>
                <p class="mt-1.5 text-sm leading-6 text-zinc-700">{{ $diagnostics['recommended_for'] ?: '—' }}</p>
            </div>

            @php $activeModules = $modules->where('enabled', true)->take(12); @endphp
            @if ($activeModules->isNotEmpty())
                <div class="mt-5">
                    <p class="text-[11px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.enabled_modules') }}</p>
                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                        @foreach ($activeModules as $module)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-[12px] font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>{{ $module['name'] }}
                            </span>
                        @endforeach
                        @if ($enabledModules > 12)
                            <span class="inline-flex items-center rounded-lg bg-zinc-100 px-2.5 py-1 text-[12px] font-medium text-zinc-500">+{{ $enabledModules - 12 }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        {{-- Available packs --}}
        <section class="rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] lg:p-7">
            <div class="space-y-1.5">
                <p class="text-[12px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.available_packs') }}</p>
                <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ __('admin::references.diagnostics.available_packs') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.pack_cards_help') }}</p>
            </div>

            <div class="mt-4 space-y-2.5">
                @foreach ($availablePacks as $pack)
                    @php $isActive = $pack['key'] === $diagnostics['active_pack']; @endphp
                    <div @class([
                        'rounded-2xl p-4 transition-all duration-200',
                        'bg-emerald-50/50 ring-1 ring-inset ring-emerald-200' => $isActive,
                        'bg-white ring-1 ring-inset ring-zinc-200/80 hover:ring-zinc-300' => ! $isActive,
                    ])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span @class(['h-2 w-2 rounded-full', 'bg-emerald-500' => $isActive, 'bg-zinc-300' => ! $isActive])></span>
                                    <p class="truncate text-[15px] font-semibold tracking-tight text-zinc-900">{{ $pack['label'] }}</p>
                                </div>
                                <p class="text-[13px] leading-6 text-zinc-500">{{ $pack['description'] }}</p>
                            </div>
                            @if ($isActive)
                                <span class="inline-flex shrink-0 rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">{{ __('admin::references.diagnostics.active') }}</span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center gap-2 text-[12px]">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-50 px-2.5 py-1 font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200/70">
                                <span class="text-zinc-400">{{ __('admin::references.diagnostics.visible_menus') }}</span>
                                <span class="font-semibold text-zinc-900 tabular-nums">{{ $pack['menu_count'] }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-50 px-2.5 py-1 font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200/70">
                                <span class="text-zinc-400">{{ __('admin::references.diagnostics.enabled_permissions') }}</span>
                                <span class="font-semibold text-zinc-900 tabular-nums">{{ $pack['permission_count'] }}</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ───────────────────────── Workflow + Approval defaults ───────────────────────── --}}
    <div class="grid gap-5 xl:grid-cols-2">
        {{-- Workflow defaults --}}
        <section class="rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] lg:p-7">
            <div class="space-y-1.5">
                <p class="text-[12px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.workflow_defaults') }}</p>
                <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ __('admin::references.diagnostics.workflow_defaults') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.workflow_defaults_help') }}</p>
            </div>

            <div class="mt-4 space-y-2.5">
                @foreach ($diagnostics['workflow_defaults'] as $settings)
                    <div class="rounded-2xl bg-zinc-50/70 p-4 ring-1 ring-inset ring-zinc-200/60">
                        <p class="text-[15px] font-semibold tracking-tight text-zinc-900">{{ $settings['label'] }}</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @forelse (($settings['tabs'] ?? []) as $tab)
                                <span class="inline-flex rounded-lg bg-white px-2.5 py-1 text-[12px] font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200">{{ $tab }}</span>
                            @empty
                            @endforelse
                            @foreach (($settings['test_tabs'] ?? []) as $tab)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1 text-[12px] font-medium text-sky-700 ring-1 ring-inset ring-sky-100">{{ $tab }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Approval defaults --}}
        <section class="rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] lg:p-7">
            <div class="space-y-1.5">
                <p class="text-[12px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.approval_defaults') }}</p>
                <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ __('admin::references.diagnostics.approval_defaults') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.approval_defaults_help') }}</p>
            </div>

            <div class="mt-4 space-y-2.5">
                @foreach ($diagnostics['self_service_approval'] as $requestType => $settings)
                    <div class="rounded-2xl bg-zinc-50/70 p-4 ring-1 ring-inset ring-zinc-200/60">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[15px] font-semibold tracking-tight text-zinc-900">{{ __('personnel::my_hr.requests.types.'.$requestType) }}</p>
                            @if (in_array($requestType, ['leave', 'vacation', 'business_trip'], true))
                                <a href="{{ route('admin.self-service-approval-routes', ['edit' => $requestType]) }}" wire:navigate
                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[12px] font-medium text-zinc-500 transition hover:bg-white hover:text-zinc-900">
                                    {{ __('admin::references.diagnostics.customize') }}
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </a>
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ([
                                ['label' => __('admin::references.fields.include_primary_approver'), 'value' => (bool) ($settings['include_primary_approver'] ?? false)],
                                ['label' => __('admin::references.fields.include_upper_approver'), 'value' => (bool) ($settings['include_upper_approver'] ?? false)],
                                ['label' => __('admin::references.fields.hr_always_included'), 'value' => (bool) ($settings['hr_always_included'] ?? false)],
                            ] as $row)
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[12px] font-medium ring-1 ring-inset',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-100' => $row['value'],
                                    'bg-white text-zinc-400 ring-zinc-200' => ! $row['value'],
                                ])>
                                    @if ($row['value'])
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    @else
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    @endif
                                    {{ $row['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ───────────────────────── Stored overrides ───────────────────────── --}}
    <section class="rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 shadow-[0_1px_3px_rgba(15,23,42,0.04)] lg:p-7">
        <div class="flex items-center justify-between gap-4">
            <div class="space-y-1.5">
                <p class="text-[12px] font-medium text-zinc-400">{{ __('admin::references.diagnostics.stored_overrides') }}</p>
                <h2 class="text-[22px] font-semibold tracking-tight text-zinc-900">{{ __('admin::references.diagnostics.stored_overrides') }}</h2>
                <p class="text-sm leading-7 text-zinc-500">{{ __('admin::references.diagnostics.stored_overrides_help') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2.5">
                @if ($approvalOverrides->isNotEmpty())
                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1.5 text-[12px] font-semibold text-zinc-600 tabular-nums">{{ $approvalOverrides->count() }}</span>
                @endif
                <a href="{{ route('admin.self-service-approval-routes') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-3.5 py-2 text-[12.5px] font-semibold text-white shadow-sm transition hover:bg-zinc-800">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    {{ __('admin::references.diagnostics.manage_overrides') }}
                </a>
            </div>
        </div>

        @if ($approvalOverrides->isEmpty())
            <div class="mt-5 grid place-items-center gap-3 rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/50 px-5 py-12 text-center">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-zinc-300 ring-1 ring-inset ring-zinc-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M12 9v6"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                <p class="max-w-sm text-sm leading-6 text-zinc-500">Bu deployment üçün ayrıca saxlanmış təsdiq siyasəti yoxdur — sistem standart paket qaydaları ilə işləyir.</p>
                <a href="{{ route('admin.self-service-approval-routes', ['edit' => 'leave']) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-4 py-2 text-[13px] font-semibold text-white shadow-sm transition hover:bg-zinc-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('admin::references.diagnostics.manage_overrides') }}
                </a>
            </div>
        @else
            <div class="mt-5 grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                @foreach ($approvalOverrides as $route)
                    <div class="rounded-2xl bg-zinc-50/70 p-4 ring-1 ring-inset ring-zinc-200/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-[15px] font-semibold tracking-tight text-zinc-900">{{ __('personnel::my_hr.requests.types.'.$route->request_type) }}</p>
                                    @if (in_array($route->request_type, ['leave', 'vacation', 'business_trip'], true))
                                        <a href="{{ route('admin.self-service-approval-routes', ['edit' => $route->request_type]) }}" wire:navigate
                                           title="{{ __('admin::references.diagnostics.edit_policy') }}"
                                           class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white hover:text-zinc-900">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[13px] text-zinc-500">Bu müraciət növü üçün fərdi qayda.</p>
                            </div>
                            <span @class([
                                'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
                                'bg-emerald-50 text-emerald-700 ring-emerald-100' => (bool) $route->is_active,
                                'bg-white text-zinc-400 ring-zinc-200' => ! (bool) $route->is_active,
                            ])>
                                <span @class(['h-1.5 w-1.5 rounded-full', 'bg-emerald-500' => (bool) $route->is_active, 'bg-zinc-300' => ! (bool) $route->is_active])></span>
                                {{ (bool) $route->is_active ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no') }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-1.5">
                            @foreach ([
                                ['label' => __('admin::references.fields.include_primary_approver'), 'value' => (bool) $route->include_primary_approver],
                                ['label' => __('admin::references.fields.include_upper_approver'), 'value' => (bool) $route->include_upper_approver],
                                ['label' => __('admin::references.fields.hr_always_included'), 'value' => (bool) $route->hr_always_included],
                            ] as $row)
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 ring-1 ring-inset ring-zinc-200/70">
                                    <span class="text-[12px] font-medium text-zinc-600">{{ $row['label'] }}</span>
                                    <span @class([
                                        'inline-flex h-5 w-5 items-center justify-center rounded-full',
                                        'bg-emerald-100 text-emerald-700' => $row['value'],
                                        'bg-zinc-100 text-zinc-400' => ! $row['value'],
                                    ])>
                                        @if ($row['value'])
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        @else
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
