@php
    $personnel = $this->personnel;
    $cards = [
        [
            'label' => __('personnel::my_hr.summary.tabel_no'),
            'value' => $personnel->tabel_no ?: '—',
        ],
        [
            'label' => __('personnel::my_hr.summary.position'),
            'value' => $personnel->position?->name ?: '—',
        ],
        [
            'label' => __('personnel::my_hr.summary.structure'),
            'value' => $personnel->structure?->fullStructureName(includeRoot: true) ?: '—',
        ],
        [
            'label' => __('personnel::my_hr.summary.email'),
            'value' => $personnel->email ?: '—',
        ],
        [
            'label' => __('personnel::my_hr.summary.mobile'),
            'value' => $personnel->mobile ?: $personnel->phone ?: '—',
        ],
        [
            'label' => __('personnel::my_hr.summary.joined_at'),
            'value' => optional($personnel->join_work_date)->format('d.m.Y') ?: '—',
        ],
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.summary.profile_kicker') }}</x-ui.field-label>
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ $personnel->fullname }}</h2>
                <p class="text-sm text-zinc-500">{{ $personnel->email ?: $personnel->mobile ?: $personnel->phone ?: __('personnel::my_hr.messages.contact_not_available') }}</p>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                {{ __('personnel::my_hr.summary.employee_context') }}
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ $card['label'] }}</x-ui.field-label>
                    <p class="mt-2 text-base font-semibold tracking-tight leading-6 text-zinc-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
