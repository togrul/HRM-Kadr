@props([
    'title',
    'subtitle' => null,
    'backToLogin' => false,
])

{{-- Shared split shell for every guest screen: form column on the left, brand column on the
     right. Pages supply only their fields, so login and the password flows never drift apart. --}}
<x-guest-layout>
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
        {{-- ===================== form column ===================== --}}
        <div class="flex items-center justify-center bg-white px-6 py-10">
            <div class="w-full max-w-[396px]">
                <a href="/" class="mb-8 flex items-center gap-3">
                    <x-application-logo size="wordmark" />
                    <span class="border-l border-hairline pl-3 leading-tight">
                        <span class="block text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('ui::common.labels.breadcrumb_root') }}</span>
                        <span class="block text-[11.5px] text-ink-faint">{{ config('app.name') }}</span>
                    </span>
                </a>

                <h1 class="text-[21px] font-semibold leading-tight tracking-[-0.03em] text-ink">{{ $title }}</h1>

                @if (filled($subtitle))
                    <p class="mt-1.5 text-[13px] leading-5 text-ink-faint">{{ $subtitle }}</p>
                @endif

                <x-auth-session-status class="mt-5" :status="session('status')" />

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-rose-200 bg-[#ffe4e6] px-3.5 py-3 text-[12.5px] text-[#be123c]">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{ $slot }}

                <div class="mt-8 flex items-center justify-between text-[11.5px] text-ink-faint">
                    @if ($backToLogin)
                        <a href="{{ route('login') }}" class="underline-offset-2 transition hover:text-ink hover:underline">{{ __('ui::auth.links.back_to_login') }}</a>
                    @else
                        <span>{{ __('ui::auth.labels.footer_note') }}</span>
                    @endif
                    <span class="hrm-num">v{{ config('app.version', '1.0') }}</span>
                </div>
            </div>
        </div>

        {{-- ===================== brand column ===================== --}}
        <div class="hidden p-3 lg:block">
            <div class="flex h-full flex-col justify-center rounded-[22px] bg-ink px-10 py-12 text-white">
                <div>
                    <span class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[10.5px] font-semibold uppercase tracking-[0.08em] text-white/70">
                        {{ __('ui::auth.marketing.eyebrow') }}
                    </span>

                    <h2 class="mt-6 max-w-md text-[30px] font-semibold leading-[1.15] tracking-[-0.04em]">
                        {{ __('ui::auth.marketing.headline') }}
                    </h2>
                    <p class="mt-4 max-w-md text-[13.5px] leading-relaxed text-white/60">
                        {{ __('ui::auth.marketing.description') }}
                    </p>
                </div>

                @php
                    $highlights = [
                        ['icon' => 'icons.personal-affair-icon', 'title' => 'personnel', 'note' => 'personnel_note'],
                        ['icon' => 'icons.line-order-icon', 'title' => 'orders', 'note' => 'orders_note'],
                        ['icon' => 'icons.attendance-icon', 'title' => 'attendance', 'note' => 'attendance_note'],
                        ['icon' => 'icons.payroll-icon', 'title' => 'payroll', 'note' => 'payroll_note'],
                    ];

                    $moduleChips = [
                        ['icon' => 'icons.network-icon', 'label' => 'ui::menu.items.staff_table'],
                        ['icon' => 'icons.candidate-icon', 'label' => 'ui::menu.items.candidates'],
                        ['icon' => 'icons.vacation-icon', 'label' => 'ui::menu.items.vacations'],
                        ['icon' => 'icons.holiday-icon', 'label' => 'ui::menu.items.business_trips'],
                        ['icon' => 'icons.performance-icon', 'label' => 'ui::menu.items.performance'],
                        ['icon' => 'icons.training-icon', 'label' => 'ui::menu.items.training'],
                        ['icon' => 'icons.report-chart-icon', 'label' => 'ui::menu.items.reports'],
                        ['icon' => 'icons.shield-icon', 'label' => 'ui::menu.items.audit_logs'],
                    ];
                @endphp

                <div class="mt-9 grid max-w-xl grid-cols-2 gap-3">
                    @foreach ($highlights as $highlight)
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <span class="hrm-icon mb-3 flex h-8 w-8 items-center justify-center rounded-[10px] bg-white/10 text-white">
                                <x-dynamic-component :component="$highlight['icon']" color="text-current" hover="text-current" size="w-[16px] h-[16px]" />
                            </span>
                            <p class="text-[13.5px] font-semibold tracking-[-0.015em]">
                                {{ __('ui::auth.marketing.highlights.'.$highlight['title']) }}
                            </p>
                            <p class="mt-1 text-[11.5px] leading-snug text-white/50">
                                {{ __('ui::auth.marketing.highlights.'.$highlight['note']) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap gap-2">
                    @foreach ($moduleChips as $chip)
                        <span class="hrm-icon inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] text-white/60">
                            <x-dynamic-component :component="$chip['icon']" color="text-current" hover="text-current" size="w-[14px] h-[14px]" />
                            {{ __($chip['label']) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
