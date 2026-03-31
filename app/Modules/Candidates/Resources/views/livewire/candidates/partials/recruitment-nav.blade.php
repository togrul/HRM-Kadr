@php
    $tabs = [
        ['route' => 'candidates', 'label' => __('candidates::common.titles.candidates')],
        ['route' => 'candidates.requisitions', 'label' => __('candidates::recruitment.actions.open_requisitions')],
        ['route' => 'candidates.openings', 'label' => __('candidates::recruitment.actions.open_openings')],
        ['route' => 'candidates.applications', 'label' => __('candidates::recruitment.actions.open_pipeline')],
        ['route' => 'candidates.analytics', 'label' => __('candidates::recruitment.actions.open_analytics')],
    ];
@endphp

<div class="rounded-[28px] border border-slate-200 bg-white p-3 shadow-[0_24px_45px_-38px_rgba(15,23,42,0.35)]">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                {{ __('candidates::recruitment.titles.transition') }}
            </div>
            <p class="max-w-3xl text-sm leading-6 text-slate-500">
                {{ __('candidates::recruitment.labels.transition_note') }}
            </p>
        </div>

        <nav class="flex flex-wrap items-center gap-2">
            @foreach ($tabs as $tab)
                @php
                    $active = request()->routeIs($tab['route']) || request()->routeIs($tab['route'].'.*');
                @endphp
                <a
                    href="{{ route($tab['route']) }}"
                    class="{{ $active ? 'border-slate-900 bg-slate-900 text-white shadow-[0_18px_36px_-28px_rgba(15,23,42,0.8)]' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-900' }} inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition"
                >
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>
